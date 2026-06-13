<?php

namespace App\Http\Controllers;

use App\Models\DayBookEntry;
use App\Models\Party;
use App\Models\PartySubCategory;
use App\Models\Project;
use App\Models\PurchaseFile;
use App\Models\PurchaseFileDocument;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\Storage;
use App\Support\LandMeasure;
use App\Support\PartyPurchaseDefaults;
use App\Support\PurchaseLineAttributes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseFileController extends Controller
{
    public function index(Request $request)
    {
        $projectId = $request->query('project');
        $search = trim((string) $request->query('q', ''));

        $files = PurchaseFile::query()
            ->with(['project.landType', 'purchaseItems.party'])
            ->withCount(['purchaseItems', 'documents'])
            ->when($projectId, fn ($q) => $q->where('project_id', (int) $projectId))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('file_name', 'like', $like)
                        ->orWhereHas('project', fn ($p) => $p->where('name', 'like', $like))
                        ->orWhereHas('purchaseItems.party', fn ($p) => $p->where('name', 'like', $like))
                        ->orWhereHas('dealers', fn ($p) => $p->where('name', 'like', $like));
                });
            })
            ->orderByDesc('file_date')
            ->orderByDesc('id')
            ->get();

        $projects = Project::query()->orderBy('name')->get(['id', 'name']);

        $projectSaleLandFileCount = $projectId
            ? PurchaseFile::query()
                ->where('project_id', (int) $projectId)
                ->whereNotNull('sale_land_at')
                ->count()
            : 0;

        return view('purchases.files.index', compact('files', 'projects', 'projectId', 'search', 'projectSaleLandFileCount'));
    }

    public function create(Request $request)
    {
        $projects = Project::query()->with('landType')->orderBy('name')->get();
        $parties = Party::query()->orderBy('name')->get();
        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();
        $projectId = $request->query('project');

        return view('purchases.files.create', compact('projects', 'parties', 'partySubCategories', 'projectId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'file_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('purchase_files', 'file_name')->where(fn ($q) => $q->where('project_id', (int) $request->input('project_id'))),
            ],
            'file_date' => ['required', 'date'],
            'dealer_party_ids' => ['nullable', 'array'],
            'dealer_party_ids.*' => ['integer', 'distinct', 'exists:parties,id'],
            'dealer_commissions' => ['nullable', 'array'],
            'dealer_commissions.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $file = PurchaseFile::create([
            'project_id' => $validated['project_id'],
            'file_name' => trim($validated['file_name']),
            'file_date' => $validated['file_date'],
        ]);

        $dealerIds = collect($validated['dealer_party_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $this->syncDealersWithCommissions($file, $dealerIds, $request);

        return redirect()
            ->route('purchase.files.index', ['project' => $file->project_id])
            ->with('success', 'Purchase file "'.$file->file_name.'" created.');
    }

    public function sellers(PurchaseFile $purchase_file)
    {
        $purchase_file->load('project');

        $sellers = PurchaseItem::query()
            ->where('purchase_file_id', $purchase_file->id)
            ->with('party')
            ->orderByDesc('id')
            ->get();

        $parties = Party::query()->orderBy('name')->get();

        $lines = old('lines');
        if (! is_array($lines) || count($lines) < 1) {
            $lines = [[]];
        }

        $mozaSuggestions = PartyPurchaseDefaults::distinctMozas();

        return view('purchases.files.sellers', compact('purchase_file', 'sellers', 'parties', 'lines', 'mozaSuggestions'));
    }

    public function storeSellers(Request $request, PurchaseFile $purchase_file)
    {
        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.party_id' => ['required', 'integer', 'exists:parties,id'],
            'lines.*.moza' => ['nullable', 'string', 'max:255'],
            'lines.*.khasra' => ['nullable', 'string', 'max:255'],
            'lines.*.area_acre' => ['nullable', 'integer', 'min:0'],
            'lines.*.area_kanal' => ['nullable', 'integer', 'min:0'],
            'lines.*.area_marla' => ['nullable', 'integer', 'min:0'],
            'lines.*.area_sqft' => ['nullable', 'integer', 'min:0'],
            'lines.*.amount_per_acre' => ['required', 'integer', 'min:0'],
        ]);

        $partyIds = [];
        $items = [];
        foreach ($validated['lines'] as $idx => $line) {
            $attrs = PurchaseLineAttributes::fromInput(
                $line,
                "lines.{$idx}.area_acre",
                'Seller '.($idx + 1).': enter at least one positive whole number in Acre, Kanal, Marla, or Sq ft.'
            );
            $partyIds[] = $attrs['party_id'];
            $items[] = array_merge($attrs, [
                'project_id' => $purchase_file->project_id,
                'purchase_file_id' => $purchase_file->id,
            ]);
        }

        DB::transaction(function () use ($items, $purchase_file, $partyIds) {
            foreach ($items as $row) {
                PurchaseItem::create($row);
            }
            if ($partyIds !== []) {
                $purchase_file->dealers()->syncWithoutDetaching(array_unique($partyIds));
            }
        });

        return redirect()
            ->route('purchase.files.sellers', $purchase_file)
            ->with('success', count($items).' seller(s) added to file "'.$purchase_file->file_name.'".');
    }

    public function destroySeller(PurchaseFile $purchase_file, PurchaseItem $purchase_item)
    {
        if ((int) $purchase_item->purchase_file_id !== (int) $purchase_file->id) {
            abort(404);
        }
        $purchase_item->delete();

        return redirect()
            ->route('purchase.files.sellers', $purchase_file)
            ->with('success', 'Seller removed from this file.');
    }

    public function edit(PurchaseFile $purchase_file)
    {
        $purchase_file->load(['project', 'dealers']);
        $parties = Party::query()->orderBy('name')->get();
        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        return view('purchases.files.edit', [
            'file' => $purchase_file,
            'parties' => $parties,
            'partySubCategories' => $partySubCategories,
        ]);
    }

    public function update(Request $request, PurchaseFile $purchase_file)
    {
        $validated = $request->validate([
            'file_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('purchase_files', 'file_name')
                    ->where(fn ($q) => $q->where('project_id', $purchase_file->project_id))
                    ->ignore($purchase_file->id),
            ],
            'file_date' => ['required', 'date'],
            'dealer_party_ids' => ['nullable', 'array'],
            'dealer_party_ids.*' => ['integer', 'distinct', 'exists:parties,id'],
            'dealer_commissions' => ['nullable', 'array'],
            'dealer_commissions.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $purchase_file->update([
            'file_name' => trim($validated['file_name']),
            'file_date' => $validated['file_date'],
        ]);

        $dealerIds = collect($validated['dealer_party_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $this->syncDealersWithCommissions($purchase_file, $dealerIds, $request);

        return redirect()
            ->route('purchase.files.index', ['project' => $purchase_file->project_id])
            ->with('success', 'Purchase file updated.');
    }

    public function markSaleLand(PurchaseFile $purchase_file)
    {
        if ($purchase_file->isSaleLand()) {
            return redirect()
                ->route('purchase.files.index', ['project' => $purchase_file->project_id])
                ->with('info', 'Purchase file "'.$purchase_file->file_name.'" is already marked as sale land.');
        }

        $purchase_file->load('purchaseItems');
        $hasLand = $purchase_file->purchaseItems->contains(
            fn (PurchaseItem $item) => (float) $item->land_area_marla > 0
        );

        if (! $hasLand) {
            return redirect()
                ->route('purchase.files.index', ['project' => $purchase_file->project_id])
                ->with('error', 'Add sellers with land area to "'.$purchase_file->file_name.'" before marking as sale land.');
        }

        $purchase_file->update(['sale_land_at' => now()]);

        return redirect()
            ->route('projects.sale-land', ['project' => $purchase_file->project_id, 'purchase_file' => $purchase_file->id])
            ->with('success', 'Purchase file "'.$purchase_file->file_name.'" processed for sale land. Formula files generated from project exemption rules.');
    }

    public function destroy(PurchaseFile $purchase_file)
    {
        $projectId = $purchase_file->project_id;
        $name = $purchase_file->file_name;
        $purchase_file->purchaseItems()->delete();
        $purchase_file->delete();

        return redirect()
            ->route('purchase.files.index', ['project' => $projectId])
            ->with('success', 'Purchase file "'.$name.'" removed.');
    }

    public function paymentSheetPdf(PurchaseFile $purchase_file)
    {
        $purchase_file->load([
            'project',
            'dealers',
            'purchaseItems' => fn ($q) => $q->with('party')->orderBy('id'),
        ]);

        $sheet = $this->buildPurchaseFilePaymentSheet($purchase_file);

        $pdf = Pdf::loadView('purchases.files.payment-sheet-pdf', array_merge(
            ['purchaseFile' => $purchase_file],
            $sheet
        ));
        $pdf->setPaper('a4', 'portrait');

        $safeFile = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $purchase_file->file_name) ?: 'file';
        $safeProject = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $purchase_file->project?->name ?? 'project') ?: 'project';

        return $pdf->download('payment-sheet-'.$safeProject.'-'.$safeFile.'-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * @return array{
     *     sellers: \Illuminate\Support\Collection<int, PurchaseItem>,
     *     landTotalRs: float,
     *     landAreaLabel: string,
     *     paymentLines: list<array<string, mixed>>,
     *     totalPaid: float,
     *     balancePayable: float,
     *     entryCount: int,
     *     generatedAt: \Illuminate\Support\Carbon
     * }
     */
    private function buildPurchaseFilePaymentSheet(PurchaseFile $file): array
    {
        $sellers = $file->purchaseItems;
        $landTotalRs = (float) $sellers->sum('line_total_rs');
        $landAreaMarla = (float) $sellers->sum('land_area_marla');
        $landAreaLabel = $landAreaMarla > 0
            ? LandMeasure::formatAkmsLabelFromMarla($landAreaMarla)
            : '—';

        $entries = DayBookEntry::query()
            ->where('purchase_file_id', $file->id)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $partyIds = $entries->where('link_type', DayBookEntry::LINK_PARTY)->pluck('link_id')->unique()->filter()->values();
        $parties = Party::query()->whereIn('id', $partyIds)->get()->keyBy('id');

        $paidRunning = 0.0;
        $paymentLines = [];
        foreach ($entries as $entry) {
            $amount = (float) $entry->amount;
            if ($entry->type === DayBookEntry::TYPE_CASH_OUT) {
                $paidRunning += $amount;
            } else {
                $paidRunning -= $amount;
            }

            $partyName = 'General';
            if ($entry->link_type === DayBookEntry::LINK_PARTY && $entry->link_id) {
                $partyName = $parties->get((int) $entry->link_id)?->name ?? ('Party #'.$entry->link_id);
            }

            $kind = $entry->type === DayBookEntry::TYPE_CASH_IN ? 'Payment in' : 'Payment out';
            $settlement = $entry->getSettlementLabel();
            $paymentText = $kind;
            if ($settlement !== '' && $settlement !== '—') {
                $paymentText .= ' · '.$settlement;
            }

            $descParts = [];
            if ($entry->description && trim((string) $entry->description) !== '') {
                $descParts[] = trim((string) $entry->description);
            }
            if ($entry->voucher_no) {
                $descParts[] = 'Voucher '.$entry->getVoucherNumber();
            }
            $description = $descParts !== [] ? implode(' · ', $descParts) : '—';

            $paymentLines[] = [
                'date' => $entry->entry_date->format('d M Y'),
                'party' => $partyName,
                'description' => $description,
                'payment' => $paymentText,
                'amount_display' => 'Rs '.number_format($amount, 2),
                'balance' => $landTotalRs - $paidRunning,
            ];
        }

        $out = (float) $entries->where('type', DayBookEntry::TYPE_CASH_OUT)->sum('amount');
        $in = (float) $entries->where('type', DayBookEntry::TYPE_CASH_IN)->sum('amount');
        $totalPaid = $out - $in;
        $balancePayable = $landTotalRs - $totalPaid;

        return [
            'sellers' => $sellers,
            'landTotalRs' => $landTotalRs,
            'landAreaLabel' => $landAreaLabel,
            'paymentLines' => $paymentLines,
            'totalPaid' => $totalPaid,
            'balancePayable' => $balancePayable,
            'entryCount' => $entries->count(),
            'generatedAt' => now(),
        ];
    }

    public function documents(PurchaseFile $purchase_file)
    {
        $purchase_file->load([
            'project',
            'documents' => fn ($q) => $q->orderByDesc('id'),
        ]);

        return view('purchases.files.documents', compact('purchase_file'));
    }

    public function storeDocuments(Request $request, PurchaseFile $purchase_file)
    {
        $request->validate([
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'max:1024'],
        ], [
            'documents.*.max' => 'Each file must be 1 MB or smaller.',
        ]);

        $created = [];
        foreach ($request->file('documents') as $file) {
            $doc = $purchase_file->addDocument($file);
            $created[] = $this->documentPayload($doc);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => count($created).' file(s) uploaded successfully.',
                'documents' => $created,
            ]);
        }

        return redirect()
            ->route('purchase.files.documents', $purchase_file)
            ->with('success', count($created).' file(s) uploaded.');
    }

    public function destroyDocument(PurchaseFile $purchase_file, int $document)
    {
        $doc = $purchase_file->documents()->findOrFail($document);
        $doc->delete();

        return redirect()
            ->route('purchase.files.documents', $purchase_file)
            ->with('success', 'Document removed.');
    }

    /**
     * @param  list<int>  $dealerIds
     */
    private function syncDealersWithCommissions(PurchaseFile $file, array $dealerIds, Request $request): void
    {
        $commissions = $request->input('dealer_commissions', []);
        $sync = [];
        foreach ($dealerIds as $partyId) {
            $raw = $commissions[$partyId] ?? $commissions[(string) $partyId] ?? null;
            $sync[$partyId] = [
                'commission_rs' => ($raw === null || $raw === '') ? null : (int) $raw,
            ];
        }
        $file->dealers()->sync($sync);
    }

    /**
     * @return array<string, mixed>
     */
    private function documentPayload(PurchaseFileDocument $doc): array
    {
        $bytes = Storage::disk('public')->exists($doc->file_path)
            ? (int) Storage::disk('public')->size($doc->file_path)
            : 0;

        return [
            'id' => $doc->id,
            'name' => $doc->name,
            'url' => asset('storage/'.$doc->file_path),
            'size_label' => $this->formatBytes($bytes),
            'created_at' => $doc->created_at?->format('d M Y, H:i') ?? '—',
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 2).' MB';
    }
}
