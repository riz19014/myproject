<?php

namespace App\Http\Controllers;

use App\Models\DayBookEntry;
use App\Models\Party;
use App\Models\Project;
use App\Models\PurchaseFile;
use App\Models\PurchaseItem;
use App\Support\LandMeasure;
use App\Support\PartyPurchaseDefaults;
use App\Support\PurchaseLineAttributes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseItemController extends Controller
{
    public function create(Request $request)
    {
        $purchaseProjects = Project::query()
            ->with('landType')
            ->orderBy('name')
            ->get();

        $projectId = $request->query('project');
        if ($projectId === null || $projectId === '') {
            return view('purchases.create-select-project', compact('purchaseProjects'));
        }

        $project = Project::query()
            ->with(['purchaseFiles' => fn ($q) => $q->with('dealers')->orderBy('file_name')])
            ->findOrFail((int) $projectId);

        $parties = Party::query()->orderBy('name')->get();

        $lines = old('lines');
        if (! is_array($lines) || count($lines) < 1) {
            $lines = [[]];
        }

        $mozaSuggestions = PartyPurchaseDefaults::distinctMozas();

        return view('purchases.create-lines', compact('project', 'parties', 'lines', 'mozaSuggestions'));
    }

    public function suggestFileName(Request $request)
    {
        $validated = $request->validate([
            'lines' => ['present', 'array'],
            'lines.*.area_acre' => ['nullable', 'integer', 'min:0'],
            'lines.*.area_kanal' => ['nullable', 'integer', 'min:0'],
            'lines.*.area_marla' => ['nullable', 'integer', 'min:0'],
            'lines.*.area_sqft' => ['nullable', 'integer', 'min:0'],
        ]);

        $total = 0.0;
        foreach ($validated['lines'] as $line) {
            $total += LandMeasure::marlaFromAkms(
                (int) ($line['area_acre'] ?? 0),
                (int) ($line['area_kanal'] ?? 0),
                (int) ($line['area_marla'] ?? 0),
                (int) ($line['area_sqft'] ?? 0),
            );
        }

        return response()->json([
            'name' => LandMeasure::formatSpokenKanalMarlaFromMarla($total),
            'total_marla' => round($total, 4),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.party_id' => ['required', 'integer', 'exists:parties,id'],
            'lines.*.moza' => ['nullable', 'string', 'max:255'],
            'lines.*.khasra' => ['nullable', 'string', 'max:255'],
            'lines.*.area_acre' => ['nullable', 'integer', 'min:0'],
            'lines.*.area_kanal' => ['nullable', 'integer', 'min:0'],
            'lines.*.area_marla' => ['nullable', 'integer', 'min:0'],
            'lines.*.area_sqft' => ['nullable', 'integer', 'min:0'],
            'lines.*.amount_per_acre' => ['required', 'integer', 'min:0'],
            'new_file_name' => ['nullable', 'string', 'max:255'],
            'purchase_file_id' => [
                'nullable',
                'integer',
                Rule::exists('purchase_files', 'id')->where(fn ($q) => $q->where('project_id', (int) $request->input('project_id'))),
            ],
            'dealer_party_ids' => ['nullable', 'array'],
            'dealer_party_ids.*' => ['integer', 'distinct', 'exists:parties,id'],
        ]);

        $project = Project::query()->findOrFail($validated['project_id']);

        $purchaseFileId = $this->resolvePurchaseFileId($project, $request);

        $items = [];
        foreach ($validated['lines'] as $idx => $line) {
            $attrs = $this->computeLineAttributes(
                $line,
                "lines.{$idx}.area_acre",
                'Line '.($idx + 1).': enter at least one positive whole number in Acre, Kanal, Marla, or Sq ft.'
            );
            $items[] = array_merge([
                'project_id' => $project->id,
                'purchase_file_id' => $purchaseFileId,
            ], $attrs);
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

        $parties = Party::query()->orderBy('name')->get();

        $project->load(['purchaseFiles' => fn ($q) => $q->with('dealers')->orderBy('file_name')]);

        return view('purchases.edit', [
            'project' => $project,
            'parties' => $parties,
            'item' => $purchase_item,
        ]);
    }

    public function update(Request $request, PurchaseItem $purchase_item)
    {
        $project = $purchase_item->project;

        $validated = $request->validate([
            'party_id' => ['required', 'integer', 'exists:parties,id'],
            'moza' => ['nullable', 'string', 'max:255'],
            'khasra' => ['nullable', 'string', 'max:255'],
            'area_acre' => ['nullable', 'integer', 'min:0'],
            'area_kanal' => ['nullable', 'integer', 'min:0'],
            'area_marla' => ['nullable', 'integer', 'min:0'],
            'area_sqft' => ['nullable', 'integer', 'min:0'],
            'amount_per_acre' => ['required', 'integer', 'min:0'],
            'new_file_name' => ['nullable', 'string', 'max:255'],
            'purchase_file_id' => [
                'nullable',
                'integer',
                Rule::exists('purchase_files', 'id')->where(fn ($q) => $q->where('project_id', $project->id)),
            ],
            'dealer_party_ids' => ['nullable', 'array'],
            'dealer_party_ids.*' => ['integer', 'distinct', 'exists:parties,id'],
        ]);

        $attrs = $this->computeLineAttributes(
            $validated,
            'area_acre',
            'Enter at least one positive whole number in Acre, Kanal, Marla, or Sq ft.'
        );

        $purchaseFileId = $this->resolvePurchaseFileId($project, $request);

        $purchase_item->update(array_merge($attrs, ['purchase_file_id' => $purchaseFileId]));

        return redirect()->route('purchase.index')
            ->with('success', 'Purchase line #'.$purchase_item->id.' updated.');
    }

    public function destroy(PurchaseItem $purchase_item)
    {
        $project = $purchase_item->project;
        $purchase_item->delete();

        return redirect()->route('purchase.index')
            ->with('success', 'Purchase line removed.');
    }

    public function pdf()
    {
        $purchaseItems = PurchaseItem::query()
            ->with(['project', 'party', 'purchaseFile.dealers'])
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

        $bookTotal = (float) $project->purchaseItems()->sum('line_total_rs');

        $landAkms = $this->purchaseProjectLandAkmsLine($project);

        $rows = [];
        $rows[] = [
            'is_opening' => true,
            'date' => '',
            'party' => '—',
            'description' => 'Opening balance — total purchase line amount for this project (amount remaining before daybook payments below).',
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
        $marla = (float) $project->purchaseItems()->sum('land_area_marla');
        if ($marla <= 0) {
            $project->loadMissing('parties');
            $marla = LandMeasure::partiesTotalMarla($project->parties);
        }
        if ($marla <= 0) {
            return '—';
        }

        return LandMeasure::formatAkmsLabelFromMarla($marla);
    }

    /**
     * New file name wins over an existing file pick. Empty both leaves purchase lines ungrouped (null file).
     */
    private function resolvePurchaseFileId(Project $project, Request $request): ?int
    {
        $newName = trim((string) $request->input('new_file_name', ''));
        if ($newName !== '') {
            Validator::make(
                ['new_file_name' => $newName],
                [
                    'new_file_name' => [
                        'required',
                        'string',
                        'max:255',
                        Rule::unique('purchase_files', 'file_name')->where(fn ($q) => $q->where('project_id', $project->id)),
                    ],
                ],
                ['new_file_name.unique' => 'A purchase file with this name already exists for this project. Choose another name or link to the existing file.']
            )->validate();

            $file = $project->purchaseFiles()->create([
                'file_name' => $newName,
            ]);

            $this->syncPurchaseFileDealers($file, $request);

            return $file->id;
        }

        $raw = $request->input('purchase_file_id');
        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;
        $file = $project->purchaseFiles()->whereKey($id)->first();
        if (! $file) {
            throw ValidationException::withMessages([
                'purchase_file_id' => ['Pick a purchase file that belongs to this project.'],
            ]);
        }

        if ($request->has('dealer_party_ids')) {
            $this->syncPurchaseFileDealers($file, $request);
        }

        return $id;
    }

    private function syncPurchaseFileDealers(PurchaseFile $file, Request $request): void
    {
        $dealerIds = collect($request->input('dealer_party_ids', []))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($dealerIds !== []) {
            $file->dealers()->sync($dealerIds);
        }
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function computeLineAttributes(array $line, string $areaErrorKey, ?string $areaErrorMessage = null): array
    {
        return PurchaseLineAttributes::fromInput($line, $areaErrorKey, $areaErrorMessage);
    }
}
