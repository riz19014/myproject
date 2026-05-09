<?php

namespace App\Http\Controllers;

use App\Models\DayBookEntry;
use App\Models\Party;
use App\Models\Project;
use App\Models\PurchaseItem;
use App\Support\LandMeasure;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseItemController extends Controller
{
    public function create(Request $request)
    {
        $purchaseProjects = Project::query()
            ->with('landType')
            ->where('field_type', 'purchase')
            ->orderBy('name')
            ->get();

        $projectId = $request->query('project');
        if ($projectId === null || $projectId === '') {
            return view('purchases.create-select-project', compact('purchaseProjects'));
        }

        $project = Project::query()
            ->where('field_type', 'purchase')
            ->findOrFail((int) $projectId);

        $parties = Party::query()->orderBy('name')->get();

        $lines = old('lines');
        if (! is_array($lines) || count($lines) < 1) {
            $lines = [[]];
        }

        return view('purchases.create-lines', compact('project', 'parties', 'lines'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.party_id' => ['required', 'integer', 'exists:parties,id'],
            'lines.*.moza' => ['nullable', 'string', 'max:255'],
            'lines.*.khasra' => ['nullable', 'string', 'max:255'],
            'lines.*.area_acre' => ['required', 'integer', 'min:0'],
            'lines.*.area_kanal' => ['required', 'integer', 'min:0'],
            'lines.*.area_marla' => ['required', 'integer', 'min:0'],
            'lines.*.area_sqft' => ['required', 'integer', 'min:0'],
            'lines.*.amount_per_acre' => ['required', 'numeric', 'min:0'],
        ]);

        $project = Project::query()->findOrFail($validated['project_id']);
        if ($project->field_type !== 'purchase') {
            abort(403, 'Only purchase-type projects accept purchase records.');
        }

        $items = [];
        foreach ($validated['lines'] as $idx => $line) {
            $attrs = $this->computeLineAttributes(
                $line,
                "lines.{$idx}.area_acre",
                'Line '.($idx + 1).': enter at least one positive whole number in Acre, Kanal, Marla, or Sq ft.'
            );
            $items[] = array_merge(['project_id' => $project->id], $attrs);
        }

        DB::transaction(function () use ($items) {
            foreach ($items as $row) {
                PurchaseItem::create($row);
            }
        });

        return redirect()->route('purchase.index')
            ->with('success', count($items).' purchase line(s) saved for '.$project->name.'.');
    }

    public function edit(PurchaseItem $purchase_item)
    {
        $project = $purchase_item->project;
        if ($project->field_type !== 'purchase') {
            abort(404);
        }

        $parties = Party::query()->orderBy('name')->get();

        return view('purchases.edit', [
            'project' => $project,
            'parties' => $parties,
            'item' => $purchase_item,
        ]);
    }

    public function update(Request $request, PurchaseItem $purchase_item)
    {
        $project = $purchase_item->project;
        if ($project->field_type !== 'purchase') {
            abort(404);
        }

        $validated = $request->validate([
            'party_id' => ['required', 'integer', 'exists:parties,id'],
            'moza' => ['nullable', 'string', 'max:255'],
            'khasra' => ['nullable', 'string', 'max:255'],
            'area_acre' => ['required', 'integer', 'min:0'],
            'area_kanal' => ['required', 'integer', 'min:0'],
            'area_marla' => ['required', 'integer', 'min:0'],
            'area_sqft' => ['required', 'integer', 'min:0'],
            'amount_per_acre' => ['required', 'numeric', 'min:0'],
        ]);

        $attrs = $this->computeLineAttributes(
            $validated,
            'area_acre',
            'Enter at least one positive whole number in Acre, Kanal, Marla, or Sq ft.'
        );

        $purchase_item->update($attrs);

        return redirect()->route('purchase.index')
            ->with('success', 'Purchase line #'.$purchase_item->id.' updated.');
    }

    public function destroy(PurchaseItem $purchase_item)
    {
        $project = $purchase_item->project;
        if ($project->field_type !== 'purchase') {
            abort(404);
        }
        $purchase_item->delete();

        return redirect()->route('purchase.index')
            ->with('success', 'Purchase line removed.');
    }

    public function pdf()
    {
        $purchaseItems = PurchaseItem::query()
            ->with(['project', 'party'])
            ->orderByDesc('id')
            ->limit(400)
            ->get();

        $purchaseTotalMarla = (float) $purchaseItems->sum(fn ($i) => (float) $i->land_area_marla);
        $purchaseTotalRs = (float) $purchaseItems->sum(fn ($i) => (float) $i->line_total_rs);
        $purchaseLineCount = $purchaseItems->count();
        $generatedAt = now();

        $pdf = Pdf::loadView('purchases.lines-pdf', [
            'purchaseItems' => $purchaseItems,
            'purchaseTotalMarla' => $purchaseTotalMarla,
            'purchaseTotalRs' => $purchaseTotalRs,
            'purchaseLineCount' => $purchaseLineCount,
            'generatedAt' => $generatedAt,
        ]);
        $pdf->setPaper('a4', 'landscape');

        $filename = 'purchase-land-'.$generatedAt->format('Y-m-d-His').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Purchase ledger PDF: per purchase-type project, opening book total then daybook lines
     * (party, payment description, paid amount, running balance remaining on the deal).
     */
    public function ledgerPdf()
    {
        $projects = Project::query()
            ->with('landType')
            ->where('field_type', 'purchase')
            ->orderBy('name')
            ->get();

        $sections = $projects->map(fn (Project $p) => $this->buildPurchaseProjectLedgerSection($p))->all();

        $generatedAt = now();
        $totalDaybookLines = (int) collect($sections)->sum(fn (array $s) => $s['entry_count']);

        $pdf = Pdf::loadView('purchases.ledger-pdf', [
            'sections' => $sections,
            'generatedAt' => $generatedAt,
            'projectCount' => $projects->count(),
            'totalDaybookLines' => $totalDaybookLines,
        ]);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'purchase-land-ledger-'.$generatedAt->format('Y-m-d-His').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @return array{project: Project, land_akms: string, book_total: float, rows: list<array<string, mixed>>, entry_count: int}
     */
    private function buildPurchaseProjectLedgerSection(Project $project): array
    {
        $project->loadMissing('landType');

        $entries = DayBookEntry::query()
            ->linkedToProject($project)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $partyIds = $entries->where('link_type', DayBookEntry::LINK_PARTY)->pluck('link_id')->unique()->filter()->values();
        $parties = Party::query()->whereIn('id', $partyIds)->get()->keyBy('id');

        $bookTotal = $project->total_amount !== null && $project->total_amount !== ''
            ? (float) $project->total_amount
            : 0.0;

        $landAkms = $this->purchaseProjectLandAkmsLine($project);

        $rows = [];
        $rows[] = [
            'is_opening' => true,
            'date' => '',
            'party' => '—',
            'description' => 'Opening balance — project book total for this land (amount remaining before daybook payments below).',
            'paid_display' => '—',
            'balance' => $bookTotal,
        ];

        $balance = $bookTotal;
        foreach ($entries as $e) {
            $amt = (float) $e->amount;
            if ($e->type === DayBookEntry::TYPE_CASH_OUT) {
                $balance -= $amt;
                $paidDisplay = 'Rs '.number_format($amt, 2);
            } else {
                $balance += $amt;
                $paidDisplay = '-Rs '.number_format($amt, 2).' (payment in)';
            }

            $partyName = 'General';
            if ($e->link_type === DayBookEntry::LINK_PARTY && $e->link_id) {
                $partyName = $parties->get((int) $e->link_id)?->name ?? ('Party #'.$e->link_id);
            }

            $descParts = [];
            if ($e->description && trim((string) $e->description) !== '') {
                $descParts[] = trim((string) $e->description);
            }
            $kind = $e->type === DayBookEntry::TYPE_CASH_IN ? 'Payment in' : 'Payment out';
            $descParts[] = $kind;
            $settlement = $e->getSettlementLabel();
            if ($settlement !== '' && $settlement !== '—') {
                $descParts[] = $settlement;
            }
            $description = implode(' · ', $descParts);

            $rows[] = [
                'is_opening' => false,
                'date' => $e->entry_date->format('d-M-y'),
                'party' => $partyName,
                'description' => $description,
                'paid_display' => $paidDisplay,
                'balance' => $balance,
            ];
        }

        return [
            'project' => $project,
            'land_akms' => $landAkms,
            'book_total' => $bookTotal,
            'rows' => $rows,
            'entry_count' => $entries->count(),
        ];
    }

    private function purchaseProjectLandAkmsLine(Project $project): string
    {
        if ($project->land_area === null || $project->land_area === '' || ! $project->land_area_unit) {
            return '—';
        }
        $unit = (string) $project->land_area_unit;
        if (! in_array($unit, ['acre', 'kanal', 'marla', 'sqft'], true)) {
            return '—';
        }
        $marla = LandMeasure::toMarla((float) $project->land_area, $unit);

        return LandMeasure::formatAkmsLabelFromMarla($marla);
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function computeLineAttributes(array $line, string $areaErrorKey, ?string $areaErrorMessage = null): array
    {
        $marla = LandMeasure::marlaFromAkms(
            (int) $line['area_acre'],
            (int) $line['area_kanal'],
            (int) $line['area_marla'],
            (int) $line['area_sqft'],
        );
        if ($marla <= 0) {
            throw ValidationException::withMessages([
                $areaErrorKey => [$areaErrorMessage ?? 'Enter at least one positive whole number in Acre, Kanal, Marla, or Sq ft.'],
            ]);
        }
        $acres = PurchaseItem::acresFromMarla($marla);
        $amountPerAcre = (float) $line['amount_per_acre'];
        $lineTotal = round($acres * $amountPerAcre, 2);

        return [
            'party_id' => (int) $line['party_id'],
            'moza' => $line['moza'] ?? null,
            'khasra' => $line['khasra'] ?? null,
            'area_acre' => (int) $line['area_acre'],
            'area_kanal' => (int) $line['area_kanal'],
            'area_marla' => (int) $line['area_marla'],
            'area_sqft' => (int) $line['area_sqft'],
            'land_area_marla' => round($marla, 4),
            'amount_per_acre' => $amountPerAcre,
            'line_total_rs' => $lineTotal,
        ];
    }
}
