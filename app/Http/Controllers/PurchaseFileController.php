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
use App\Support\PurchaseFileSheetGrid;
use App\Support\PurchaseLineAttributes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseFileController extends Controller
{
    private const DEALER_SUB_CATEGORY_ID = 3;

    public function index(Request $request)
    {
        $projectId = $request->query('project');
        $search = trim((string) $request->query('q', ''));

        $files = PurchaseFile::query()
            ->with(['project.landType', 'purchaseItems.party'])
            ->withCount(['purchaseItems', 'documents'])
            ->whereNull('sale_land_at')
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


        return view('purchases.files.index', compact('files', 'projects', 'projectId', 'search'));
    }

    public function create(Request $request)
    {
        $projects = Project::query()->with('landType')->orderBy('name')->get();
        $parties = Party::query()
            ->where('sub_category_id', self::DEALER_SUB_CATEGORY_ID)
            ->orderBy('name')
            ->get();
        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->where('id', self::DEALER_SUB_CATEGORY_ID)
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
            'dealer_party_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('parties', 'id')->where('sub_category_id', self::DEALER_SUB_CATEGORY_ID),
            ],
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

        $items = [];
        foreach ($validated['lines'] as $idx => $line) {
            $attrs = PurchaseLineAttributes::fromInput(
                $line,
                "lines.{$idx}.area_acre",
                'Seller '.($idx + 1).': enter at least one positive whole number in Acre, Kanal, Marla, or Sq ft.'
            );
            $items[] = array_merge($attrs, [
                'project_id' => $purchase_file->project_id,
                'purchase_file_id' => $purchase_file->id,
            ]);
        }

        DB::transaction(function () use ($items) {
            foreach ($items as $row) {
                PurchaseItem::create($row);
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

        $incorrectDealerIds = $purchase_file->dealers
            ->filter(fn (Party $party) => (int) $party->sub_category_id !== self::DEALER_SUB_CATEGORY_ID)
            ->pluck('id')
            ->all();

        if ($incorrectDealerIds !== []) {
            $purchase_file->dealers()->detach($incorrectDealerIds);
            $purchase_file->load('dealers');
        }

        $parties = Party::query()
            ->where('sub_category_id', self::DEALER_SUB_CATEGORY_ID)
            ->orderBy('name')
            ->get();

        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->where('id', self::DEALER_SUB_CATEGORY_ID)
            ->get();

        $selectedDealerIds = $purchase_file->dealers->pluck('id')->all();
        $dealerCommissions = $purchase_file->dealers
            ->mapWithKeys(fn ($dealer) => [$dealer->id => $dealer->pivot->commission_rs])
            ->all();

        return view('purchases.files.edit', [
            'file' => $purchase_file,
            'parties' => $parties,
            'partySubCategories' => $partySubCategories,
            'selectedDealerIds' => $selectedDealerIds,
            'dealerCommissions' => $dealerCommissions,
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
            'dealer_party_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('parties', 'id')->where('sub_category_id', self::DEALER_SUB_CATEGORY_ID),
            ],
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

    public function show(PurchaseFile $purchase_file)
    {
        $purchase_file->load([
            'project',
            'dealers.subCategory.category',
            'purchaseItems' => fn ($q) => $q->with(['party.subCategory.category'])->orderBy('id'),
        ])->loadCount('documents');

        $data = $this->buildPurchaseFileViewData($purchase_file);
        $ledger = $this->buildPurchaseFileLedger($purchase_file, $data);

        return view('purchases.files.show', array_merge(
            ['purchaseFile' => $purchase_file],
            $data,
            $ledger
        ));
    }

    public function paymentSheetPdf(PurchaseFile $purchase_file)
    {
        $purchase_file->load([
            'project',
            'dealers',
            'purchaseItems' => fn ($q) => $q->with('party')->orderBy('id'),
        ]);

        $sheet = $this->buildPurchaseFileViewData($purchase_file);

        $pdf = Pdf::loadView('purchases.files.payment-sheet-pdf', array_merge(
            ['purchaseFile' => $purchase_file],
            $sheet
        ));
        $pdf->setPaper('a4', 'portrait');

        $safeFile = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $purchase_file->file_name) ?: 'file';
        $safeProject = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $purchase_file->project?->name ?? 'project') ?: 'project';

        return $pdf->download('payment-sheet-'.$safeProject.'-'.$safeFile.'-'.now()->format('Y-m-d').'.pdf');
    }

    public function viewPdf(Request $request, PurchaseFile $purchase_file)
    {
        $purchase_file->load([
            'project',
            'dealers',
            'purchaseItems' => fn ($q) => $q->with('party')->orderBy('id'),
        ]);

        $data = $this->buildPurchaseFileViewData($purchase_file);
        $data['includePaymentOpening'] = true;
        $sheetGrid = PurchaseFileSheetGrid::build($purchase_file, $data);
        $selection = $this->parseSheetSelection($request, $sheetGrid);
        $sheetGrid = PurchaseFileSheetGrid::filter($sheetGrid, $selection['columns'], $selection['items']);

        $pdf = Pdf::loadView('purchases.files.view-pdf', [
            'purchaseFile' => $purchase_file,
            'sheetGrid' => $sheetGrid,
            'generatedAt' => now(),
        ]);
        $pdf->setPaper('a4', 'landscape');

        $safeFile = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $purchase_file->file_name) ?: 'file';
        $safeProject = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $purchase_file->project?->name ?? 'project') ?: 'project';

        return $pdf->download('purchase-file-'.$safeProject.'-'.$safeFile.'-'.now()->format('Y-m-d').'.pdf');
    }

    public function ledgerPdf(Request $request, PurchaseFile $purchase_file)
    {
        $sectionKey = $request->query('section');
        if (! is_string($sectionKey) || $sectionKey === '') {
            abort(422, 'Select a party first.');
        }

        $purchase_file->load([
            'project',
            'dealers',
            'purchaseItems' => fn ($q) => $q->with('party')->orderBy('id'),
        ]);

        $data = $this->buildPurchaseFileViewData($purchase_file);
        $ledger = $this->buildPurchaseFileLedger($purchase_file, $data);

        $block = $this->ledgerSectionBlockForPdf(
            $ledger['ledgerTree'],
            $ledger['ledgerSections'],
            $sectionKey
        );

        if (! $block) {
            abort(404, 'Ledger section not found.');
        }

        $section = $block['section'];
        $partySlug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $section['title'] ?? 'party') ?: 'party';

        $pdf = Pdf::loadView('purchases.files.ledger-pdf', [
            'purchaseFile' => $purchase_file,
            'generatedAt' => now(),
            'ledgerSectionsOrdered' => [$block],
            'ledgerPdfSummary' => $this->buildLedgerPdfSummary($purchase_file, $data, $sectionKey, $section),
            'singleParty' => true,
        ]);
        $pdf->setPaper('a4', 'portrait');

        $safeFile = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $purchase_file->file_name) ?: 'file';
        $safeProject = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $purchase_file->project?->name ?? 'project') ?: 'project';

        return $pdf->download('purchase-ledger-'.$safeProject.'-'.$safeFile.'-'.$partySlug.'-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * @param  list<array<string, mixed>>  $ledgerTree
     * @param  array<string, array<string, mixed>>  $ledgerSections
     * @return array{key: string, group: ?string, section: array<string, mixed>}|null
     */
    private function ledgerSectionBlockForPdf(array $ledgerTree, array $ledgerSections, string $sectionKey): ?array
    {
        if (! isset($ledgerSections[$sectionKey])) {
            return null;
        }

        $group = null;
        foreach ($ledgerTree as $subCategory) {
            if (($subCategory['all_key'] ?? '') === $sectionKey) {
                $group = $subCategory['label'] ?? null;
                break;
            }

            foreach ($subCategory['parties'] ?? [] as $party) {
                if (($party['key'] ?? '') === $sectionKey) {
                    $group = $subCategory['label'] ?? null;
                    break 2;
                }
            }
        }

        return [
            'key' => $sectionKey,
            'group' => $group,
            'section' => $ledgerSections[$sectionKey],
        ];
    }

    /**
     * Compact file summary for purchase ledger PDF header block.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private function buildLedgerPdfSummary(PurchaseFile $file, array $data, string $sectionKey, array $section): array
    {
        $sellers = $data['sellers'];
        $ownerNames = $sellers
            ->map(fn ($seller) => trim((string) ($seller->party?->name ?? '')))
            ->filter()
            ->unique()
            ->values();

        $headline = trim((string) ($section['title'] ?? ''));

        if (preg_match('/^party_(\d+)$/', $sectionKey, $matches)) {
            $partyId = (int) $matches[1];
            $partyMarla = (float) $sellers->where('party_id', $partyId)->sum('land_area_marla');
            $partyName = $sellers->firstWhere('party_id', $partyId)?->party?->name ?? $headline;
            if ($partyMarla > 0) {
                $headline = $this->formatLedgerPdfLandHeadline($partyMarla).' ('.$partyName.')';
            }
        } elseif (str_contains($sectionKey, '_all')) {
            $totalMarla = (float) $sellers->sum('land_area_marla');
            if ($totalMarla > 0) {
                $headline = $this->formatLedgerPdfLandHeadline($totalMarla).' (All land owners)';
            }
        }

        return [
            'headline' => $headline,
            'project_name' => $file->project?->name ?? '—',
            'file_date' => $file->file_date?->format('d M Y') ?? '—',
            'dealers' => $file->dealers->pluck('name')->filter()->implode(', ') ?: null,
            'owner_names' => $ownerNames->implode(', ') ?: null,
            'land_total_rs' => (float) ($data['landTotalRs'] ?? 0),
            'land_area_label' => (string) ($data['landAreaLabel'] ?? '—'),
        ];
    }

    private function formatLedgerPdfLandHeadline(float $marla): string
    {
        $spoken = LandMeasure::formatSpokenKanalMarlaFromMarla($marla);

        return preg_replace_callback(
            '/\b(kanal|marla|acre)\b/',
            static fn (array $match) => ucfirst($match[0]),
            $spoken
        ) ?? $spoken;
    }

    /**
     * @param  array{columns: list<array<string, mixed>>, row_count: int}  $grid
     * @return array{columns: list<string>, items: array<string, list<string>>}
     */
    private function parseSheetSelection(Request $request, array $grid): array
    {
        $allowedKeys = array_column($grid['columns'], 'key');
        $columns = $request->query('columns', $allowedKeys);
        if (! is_array($columns)) {
            $columns = [$columns];
        }
        $columns = array_values(array_intersect($columns, $allowedKeys));
        if ($columns === []) {
            $columns = $allowedKeys;
        }

        $items = [];
        $rawItems = $request->query('items', []);
        if (is_array($rawItems)) {
            foreach ($rawItems as $colKey => $ids) {
                if (! in_array((string) $colKey, $allowedKeys, true) || ! is_array($ids)) {
                    continue;
                }
                $items[(string) $colKey] = array_values(array_map('strval', $ids));
            }
        }

        return [
            'columns' => $columns,
            'items' => $items,
        ];
    }

    /**
     * @return list<string>
     */
    private function parsePurchaseFileViewSections(Request $request): array
    {
        $allowed = ['sellers', 'payments', 'expenses'];
        $sections = $request->query('sections', $allowed);
        if (! is_array($sections)) {
            $sections = [$sections];
        }
        $sections = array_values(array_intersect($sections, $allowed));

        return $sections !== [] ? $sections : $allowed;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function filterPurchaseFileViewData(array $data, Request $request, array $selectedSections): array
    {
        if (in_array('sellers', $selectedSections, true) && $request->has('seller_ids')) {
            $ids = collect($request->query('seller_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();
            $data['sellers'] = $data['sellers']->whereIn('id', $ids)->values();
            $marla = (float) $data['sellers']->sum('land_area_marla');
            $data['landTotalRs'] = (float) $data['sellers']->sum('line_total_rs');
            $data['landAreaLabel'] = $marla > 0
                ? LandMeasure::formatAkmsLabelFromMarla($marla)
                : '—';
        }

        if (in_array('payments', $selectedSections, true) && $request->has('payment_ids')) {
            $paymentIds = collect($request->query('payment_ids', []))
                ->map(fn ($id) => (string) $id)
                ->values()
                ->all();
            $includeOpening = in_array('opening', $paymentIds, true);
            $entryIds = collect($paymentIds)
                ->filter(fn ($id) => $id !== 'opening' && ctype_digit($id))
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $data['paymentRows'] = collect($data['paymentRows'])
                ->filter(fn (array $row) => in_array((int) $row['entry']->id, $entryIds, true))
                ->values()
                ->all();

            $paidRunning = 0.0;
            $landTotal = $includeOpening ? (float) $data['landTotalRs'] : 0.0;
            $filteredLines = [];
            foreach ($data['paymentRows'] as $row) {
                $entry = $row['entry'];
                $amount = (float) $entry->amount;
                if ($entry->type === DayBookEntry::TYPE_CASH_OUT) {
                    $paidRunning += $amount;
                } else {
                    $paidRunning -= $amount;
                }
                $row['balance'] = $landTotal - $paidRunning;
                $filteredLines[] = $row;
            }
            $data['paymentRows'] = $filteredLines;
            $data['paymentLines'] = collect($filteredLines)->map(fn (array $row) => [
                'date' => $row['date'],
                'party' => $row['party'],
                'description' => $row['description'],
                'payment' => $row['payment'],
                'amount_display' => $row['amount_display'],
                'balance' => $row['balance'],
            ])->all();

            $data['totalPaid'] = $this->daybookEntriesNetPaid(collect($data['paymentRows'])->pluck('entry'));
            $data['balancePayable'] = ($includeOpening ? (float) $data['landTotalRs'] : 0.0) - $data['totalPaid'];
            $data['paymentEntryCount'] = count($data['paymentRows']);
            $data['includePaymentOpening'] = $includeOpening;
        } else {
            $data['includePaymentOpening'] = true;
        }

        if (in_array('expenses', $selectedSections, true) && $request->has('expense_ids')) {
            $ids = collect($request->query('expense_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();
            $data['expenseGroups'] = collect($data['expenseGroups'])
                ->filter(fn (array $group) => in_array((int) $group['sub_category_id'], $ids, true))
                ->values()
                ->all();
            $data['totalExpenses'] = (float) collect($data['expenseGroups'])->sum('total');
            $data['expenseEntryCount'] = (int) collect($data['expenseGroups'])->sum(fn (array $g) => $g['entries']->count());
        }

        return $data;
    }

    /**
     * @return array{
     *     sellers: \Illuminate\Support\Collection<int, PurchaseItem>,
     *     landTotalRs: float,
     *     landAreaLabel: string,
     *     paymentLines: list<array<string, mixed>>,
     *     paymentRows: list<array<string, mixed>>,
     *     expenseGroups: list<array<string, mixed>>,
     *     totalPaid: float,
     *     totalExpenses: float,
     *     balancePayable: float,
     *     paymentEntryCount: int,
     *     expenseEntryCount: int,
     *     entryCount: int,
     *     generatedAt: \Illuminate\Support\Carbon
     * }
     */
    private function buildPurchaseFileViewData(PurchaseFile $file): array
    {
        $sellers = $file->purchaseItems;
        $landTotalRs = (float) $sellers->sum('line_total_rs');
        $landAreaMarla = (float) $sellers->sum('land_area_marla');
        $landAreaLabel = $landAreaMarla > 0
            ? LandMeasure::formatAkmsLabelFromMarla($landAreaMarla)
            : '—';

        $allEntries = DayBookEntry::query()
            ->where('purchase_file_id', $file->id)
            ->with(['partySubCategory.category', 'paidByParty'])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $partyIds = $allEntries->where('link_type', DayBookEntry::LINK_PARTY)->pluck('link_id')->unique()->filter()->values();
        $parties = Party::query()->whereIn('id', $partyIds)->get()->keyBy('id');

        $paymentEntries = $allEntries
            ->filter(fn (DayBookEntry $e) => $e->link_type === DayBookEntry::LINK_PARTY && $e->link_id)
            ->values();
        $expenseEntries = $allEntries
            ->filter(fn (DayBookEntry $e) => ! ($e->link_type === DayBookEntry::LINK_PARTY && $e->link_id))
            ->values();

        $paidRunning = 0.0;
        $paymentLines = [];
        $paymentRows = [];
        foreach ($paymentEntries as $entry) {
            $amount = (float) $entry->amount;
            if ($entry->type === DayBookEntry::TYPE_CASH_OUT) {
                $paidRunning += $amount;
            } else {
                $paidRunning -= $amount;
            }

            $partyName = $this->daybookEntryPartyName($entry, $parties);
            $paymentText = $this->daybookEntryPaymentText($entry);
            $description = $this->daybookEntryDescription($entry);
            $balance = $landTotalRs - $paidRunning;

            $paymentRows[] = [
                'entry' => $entry,
                'date' => $entry->entry_date->format('d M Y'),
                'party' => $partyName,
                'description' => $description,
                'payment' => $paymentText,
                'amount_display' => 'Rs '.number_format($amount, 2),
                'balance' => $balance,
            ];
            $paymentLines[] = [
                'date' => $entry->entry_date->format('d M Y'),
                'party' => $partyName,
                'description' => $description,
                'payment' => $paymentText,
                'amount_display' => 'Rs '.number_format($amount, 2),
                'balance' => $balance,
            ];
        }

        $expenseGroups = [];
        foreach ($expenseEntries->groupBy('party_sub_category_id') as $subCategoryId => $rows) {
            $rows = $rows->sortBy(fn (DayBookEntry $e) => [$e->entry_date->toDateString(), $e->id])->values();
            $first = $rows->first();
            $subCategory = $first->partySubCategory;
            $categoryName = $subCategory?->category?->name ?? '—';
            $subCategoryName = $subCategory?->name ?? '—';
            $total = $this->daybookEntriesNetPaid($rows);

            $expenseGroups[] = [
                'sub_category_id' => (int) $subCategoryId,
                'label' => $first->getPartySubCategoryLabel(),
                'category' => $categoryName,
                'sub_category' => $subCategoryName,
                'entries' => $rows,
                'total' => $total,
            ];
        }
        usort($expenseGroups, fn ($a, $b) => strcasecmp($a['label'], $b['label']));

        $totalPaid = $this->daybookEntriesNetPaid($paymentEntries);
        $totalExpenses = $this->daybookEntriesNetPaid($expenseEntries);
        $balancePayable = $landTotalRs - $totalPaid;

        return [
            'sellers' => $sellers,
            'landTotalRs' => $landTotalRs,
            'landAreaLabel' => $landAreaLabel,
            'paymentLines' => $paymentLines,
            'paymentRows' => $paymentRows,
            'expenseGroups' => $expenseGroups,
            'totalPaid' => $totalPaid,
            'totalExpenses' => $totalExpenses,
            'balancePayable' => $balancePayable,
            'paymentEntryCount' => $paymentEntries->count(),
            'expenseEntryCount' => $expenseEntries->count(),
            'entryCount' => $allEntries->count(),
            'generatedAt' => now(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{ledgerTree: list<array<string, mixed>>, ledgerSections: array<string, array<string, mixed>>}
     */
    private function buildPurchaseFileLedger(PurchaseFile $file, array $data): array
    {
        $allEntries = DayBookEntry::query()
            ->where('purchase_file_id', $file->id)
            ->with(['partySubCategory.category', 'paidByParty'])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $partyIds = collect();
        foreach ($data['sellers'] as $seller) {
            if ($seller->party_id) {
                $partyIds->push((int) $seller->party_id);
            }
        }
        foreach ($file->dealers as $dealer) {
            $partyIds->push((int) $dealer->id);
        }
        foreach ($allEntries as $entry) {
            if ($entry->link_type === DayBookEntry::LINK_PARTY && $entry->link_id) {
                $partyIds->push((int) $entry->link_id);
            }
        }
        $partyIds = $partyIds->unique()->sort()->values();

        $parties = Party::query()
            ->with(['subCategory.category', 'category'])
            ->whereIn('id', $partyIds)
            ->get()
            ->keyBy('id');

        $sections = [];

        foreach ($partyIds as $partyId) {
            $party = $parties->get($partyId);
            $landTotal = (float) $data['sellers']->where('party_id', $partyId)->sum('line_total_rs');
            $dealer = $file->dealers->firstWhere('id', $partyId);
            $commission = $dealer ? (float) ($dealer->pivot->commission_rs ?? 0) : 0.0;
            $openingAmount = $landTotal + $commission;

            $paymentEntries = $allEntries
                ->where('link_type', DayBookEntry::LINK_PARTY)
                ->where('link_id', $partyId)
                ->values();

            if ($openingAmount <= 0 && $paymentEntries->isEmpty()) {
                continue;
            }

            $key = 'party_'.$partyId;
            $landAreaMarla = (float) $data['sellers']->where('party_id', $partyId)->sum('land_area_marla');
            $landAreaSpoken = $landAreaMarla > 0
                ? LandMeasure::formatSpokenKanalMarlaFromMarla($landAreaMarla)
                : null;
            $sections[$key] = $this->buildFileLedgerPaymentSection(
                $party?->name ?? ('Party #'.$partyId),
                $this->fileLedgerPartySubtitle($party, $landTotal, $commission),
                $openingAmount,
                $landTotal,
                $commission,
                $landAreaSpoken,
                $paymentEntries,
                $parties,
                $file->file_date
            );
        }

        $generalPayments = $allEntries
            ->filter(fn (DayBookEntry $e) => ! ($e->link_type === DayBookEntry::LINK_PARTY && $e->link_id))
            ->values();
        if ($generalPayments->isNotEmpty()) {
            $key = 'general';
            $sections[$key] = $this->buildFileLedgerPaymentSection(
                'General',
                'Payments without a party on this file',
                0.0,
                0.0,
                0.0,
                null,
                $generalPayments,
                collect(),
                $file->file_date
            );
        }

        $categoryBuckets = [];
        foreach ($data['expenseGroups'] as $group) {
            $subKey = 'subcategory_'.$group['sub_category_id'];
            $sections[$subKey] = $this->buildFileLedgerExpenseSection(
                $group['sub_category'],
                $group['category'],
                $group['entries'],
                $file->file_date
            );

            $first = $group['entries']->first();
            $categoryId = (int) ($first?->partySubCategory?->category_id ?? 0);
            $categoryName = $group['category'];
            if ($categoryId > 0) {
                $categoryBuckets[$categoryId] ??= [
                    'name' => $categoryName,
                    'entries' => collect(),
                ];
                $categoryBuckets[$categoryId]['entries'] = $categoryBuckets[$categoryId]['entries']->merge($group['entries']);
            }
        }

        foreach ($categoryBuckets as $categoryId => $bucket) {
            $entries = $bucket['entries']->sortBy(fn (DayBookEntry $e) => [$e->entry_date->toDateString(), $e->id])->values();
            $key = 'category_'.$categoryId;
            $sections[$key] = $this->buildFileLedgerExpenseSection(
                $bucket['name'],
                'All subcategories in this category',
                $entries,
                $file->file_date
            );
        }

        $ledgerTree = $this->buildPurchaseFileLedgerTree($file, $data, $allEntries, $parties);

        foreach ($ledgerTree as $subNode) {
            $allKey = $subNode['all_key'] ?? null;
            if (! $allKey) {
                continue;
            }

            $partyItems = array_values(array_filter(
                $subNode['parties'] ?? [],
                static fn (array $party) => empty($party['is_all'])
            ));

            if ($partyItems === []) {
                continue;
            }

            $sections[$allKey] = $this->buildFileLedgerSubcategoryAllSection(
                $file,
                $data,
                $subNode['label'],
                $subNode['category'],
                $partyItems,
                $allEntries,
                $parties,
            );
        }

        return [
            'ledgerTree' => $ledgerTree,
            'ledgerSections' => $sections,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  \Illuminate\Support\Collection<int, DayBookEntry>  $allEntries
     * @param  \Illuminate\Support\Collection<int|string, Party>  $parties
     * @return list<array<string, mixed>>
     */
    private function buildPurchaseFileLedgerTree(PurchaseFile $file, array $data, $allEntries, $parties): array
    {
        $subCategoryPartyIds = [];

        foreach ($data['sellers'] as $seller) {
            if (! $seller->party_id) {
                continue;
            }
            $party = $parties->get((int) $seller->party_id) ?? $seller->party;
            $subId = (int) ($party?->sub_category_id ?? 0);
            if ($subId > 0) {
                $subCategoryPartyIds[$subId][(int) $seller->party_id] = true;
            }
        }

        foreach ($file->dealers as $dealer) {
            $subId = (int) ($dealer->sub_category_id ?? 0);
            if ($subId > 0) {
                $subCategoryPartyIds[$subId][(int) $dealer->id] = true;
            }
        }

        foreach ($allEntries as $entry) {
            if ($entry->link_type !== DayBookEntry::LINK_PARTY || ! $entry->link_id) {
                continue;
            }

            $partyId = (int) $entry->link_id;
            $party = $parties->get($partyId);
            $subId = (int) ($entry->party_sub_category_id ?? $party?->sub_category_id ?? 0);
            if ($subId > 0) {
                $subCategoryPartyIds[$subId][$partyId] = true;
            }
        }

        if ($subCategoryPartyIds === []) {
            return [];
        }

        $subCategories = PartySubCategory::query()
            ->with('category')
            ->whereIn('id', array_keys($subCategoryPartyIds))
            ->get()
            ->sortBy(fn (PartySubCategory $sc) => ($sc->category?->name ?? '').' — '.$sc->name)
            ->values();

        $tree = [];
        foreach ($subCategories as $subCategory) {
            $subId = (int) $subCategory->id;
            $partyIds = array_keys($subCategoryPartyIds[$subId] ?? []);
            sort($partyIds);

            $partyItems = [];
            foreach ($partyIds as $partyId) {
                $party = $parties->get($partyId);
                if (! $party) {
                    continue;
                }

                $landTotal = (float) $data['sellers']->where('party_id', $partyId)->sum('line_total_rs');
                $dealer = $file->dealers->firstWhere('id', $partyId);
                $commission = $dealer ? (float) ($dealer->pivot->commission_rs ?? 0) : 0.0;
                $paymentEntries = $allEntries
                    ->where('link_type', DayBookEntry::LINK_PARTY)
                    ->where('link_id', $partyId);

                if ($landTotal <= 0 && $commission <= 0 && $paymentEntries->isEmpty()) {
                    continue;
                }

                $partyItems[] = [
                    'party_id' => $partyId,
                    'key' => 'party_'.$partyId,
                    'label' => $party->name,
                    'meta' => $this->fileLedgerPartyMeta($party, $landTotal, $commission),
                ];
            }

            usort($partyItems, fn (array $a, array $b) => strcasecmp($a['label'], $b['label']));

            if ($partyItems === []) {
                continue;
            }

            $allKey = 'subcategory_'.$subId.'_all';
            array_unshift($partyItems, [
                'party_id' => null,
                'key' => $allKey,
                'label' => 'All parties',
                'meta' => count($partyItems).' parties · combined ledger',
                'is_all' => true,
            ]);

            $tree[] = [
                'sub_category_id' => $subId,
                'key' => 'subcategory_'.$subId,
                'all_key' => $allKey,
                'label' => $subCategory->name,
                'category' => $subCategory->category?->name ?? '—',
                'parties' => $partyItems,
            ];
        }

        return $tree;
    }

    /**
     * @param  \Illuminate\Support\Collection<int|string, Party>  $parties
     * @param  \Illuminate\Support\Collection<int, DayBookEntry>  $entries
     * @return array<string, mixed>
     */
    private function buildFileLedgerPaymentSection(
        string $title,
        ?string $subtitle,
        float $openingAmount,
        float $landTotal,
        float $commission,
        ?string $landAreaSpoken,
        $entries,
        $parties,
        ?\Carbon\CarbonInterface $fallbackDate = null,
    ): array {
        $entries = $entries->sortBy(fn (DayBookEntry $e) => [$e->entry_date->toDateString(), $e->id])->values();
        $rows = [];
        $firstEntry = $entries->first();
        $openingDate = $firstEntry?->entry_date ?? $fallbackDate;
        $openingVoucher = $firstEntry ? $firstEntry->getVoucherNumber() : null;
        $paidRunning = 0.0;

        if ($openingAmount > 0) {
            $openingParty = $title;
            if ($landAreaSpoken) {
                $openingParty .= ' ('.$landAreaSpoken.')';
            }

            $rows[] = $this->fileLedgerTableRow(
                date: $openingDate,
                voucher: $openingVoucher,
                party: $openingParty,
                paymentMethod: '—',
                debit: $openingAmount,
                credit: null,
                runningBalance: $openingAmount,
                isOpening: true,
            );
        }

        foreach ($entries as $entry) {
            $amount = (float) $entry->amount;
            $partyName = $title;
            if ($entry->link_type === DayBookEntry::LINK_PARTY && $entry->link_id) {
                $party = $parties->get((int) $entry->link_id);
                if ($party?->name) {
                    $partyName = $party->name;
                }
            }

            if ($entry->type === DayBookEntry::TYPE_CASH_OUT) {
                $paidRunning += $amount;
                $debit = null;
                $credit = $amount;
            } else {
                $paidRunning -= $amount;
                $debit = $amount;
                $credit = null;
            }

            $rows[] = $this->fileLedgerTableRow(
                date: $entry->entry_date,
                voucher: $entry->getVoucherNumber(),
                party: $partyName,
                paymentMethod: $this->fileLedgerPaymentMethodLabel($entry) ?: '—',
                paymentMethodLines: $entry->ledgerPaymentDetailLines(),
                debit: $debit,
                credit: $credit,
                runningBalance: $openingAmount > 0 ? $openingAmount - $paidRunning : null,
            );
        }

        $finalBalance = $openingAmount > 0 ? $openingAmount - $paidRunning : null;

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'rows' => $rows,
            'footer' => [
                'label' => 'Balance Payable',
                'debit' => null,
                'credit' => $paidRunning > 0 ? $paidRunning : null,
                'running_balance' => $finalBalance,
            ],
        ];
    }

    /**
     * Combined ledger for all parties in a subcategory, sorted chronologically.
     *
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $partyItems
     * @param  \Illuminate\Support\Collection<int, DayBookEntry>  $allEntries
     * @param  \Illuminate\Support\Collection<int|string, Party>  $parties
     * @return array<string, mixed>
     */
    private function buildFileLedgerSubcategoryAllSection(
        PurchaseFile $file,
        array $data,
        string $subCategoryName,
        string $categoryName,
        array $partyItems,
        $allEntries,
        $parties,
    ): array {
        $candidates = [];

        foreach ($partyItems as $item) {
            $partyId = (int) ($item['party_id'] ?? 0);
            if ($partyId <= 0) {
                continue;
            }

            $party = $parties->get($partyId);
            $landTotal = (float) $data['sellers']->where('party_id', $partyId)->sum('line_total_rs');
            $dealer = $file->dealers->firstWhere('id', $partyId);
            $commission = $dealer ? (float) ($dealer->pivot->commission_rs ?? 0) : 0.0;
            $openingAmount = $landTotal + $commission;
            $landAreaMarla = (float) $data['sellers']->where('party_id', $partyId)->sum('land_area_marla');
            $landAreaSpoken = $landAreaMarla > 0
                ? LandMeasure::formatSpokenKanalMarlaFromMarla($landAreaMarla)
                : null;
            $partyName = $party?->name ?? ('Party #'.$partyId);

            $paymentEntries = $allEntries
                ->where('link_type', DayBookEntry::LINK_PARTY)
                ->where('link_id', $partyId)
                ->sortBy(fn (DayBookEntry $e) => [$e->entry_date->toDateString(), $e->id])
                ->values();

            if ($openingAmount <= 0 && $paymentEntries->isEmpty()) {
                continue;
            }

            $firstEntry = $paymentEntries->first();
            $openingDate = $firstEntry?->entry_date ?? $file->file_date;
            $openingVoucher = $firstEntry ? $firstEntry->getVoucherNumber() : null;

            if ($openingAmount > 0) {
                $openingParty = $partyName;
                if ($landAreaSpoken) {
                    $openingParty .= ' ('.$landAreaSpoken.')';
                }

                $candidates[] = [
                    'sort' => [$openingDate?->toDateString() ?? '', 0, $partyName, 0],
                    'kind' => 'opening',
                    'date' => $openingDate,
                    'voucher' => $openingVoucher,
                    'party' => $openingParty,
                    'debit' => $openingAmount,
                ];
            }

            foreach ($paymentEntries as $entry) {
                $candidates[] = [
                    'sort' => [$entry->entry_date->toDateString(), 1, $partyName, $entry->id],
                    'kind' => 'payment',
                    'entry' => $entry,
                    'party_name' => $partyName,
                ];
            }
        }

        usort($candidates, fn (array $a, array $b) => $a['sort'] <=> $b['sort']);

        $combinedRunning = 0.0;
        $totalPaid = 0.0;
        $rows = [];

        foreach ($candidates as $candidate) {
            if ($candidate['kind'] === 'opening') {
                $combinedRunning += (float) $candidate['debit'];
                $rows[] = $this->fileLedgerTableRow(
                    date: $candidate['date'],
                    voucher: $candidate['voucher'],
                    party: $candidate['party'],
                    paymentMethod: '—',
                    debit: $candidate['debit'],
                    credit: null,
                    runningBalance: $combinedRunning,
                    isOpening: true,
                );

                continue;
            }

            /** @var DayBookEntry $entry */
            $entry = $candidate['entry'];
            $amount = (float) $entry->amount;

            if ($entry->type === DayBookEntry::TYPE_CASH_OUT) {
                $totalPaid += $amount;
                $combinedRunning -= $amount;
                $debit = null;
                $credit = $amount;
            } else {
                $totalPaid -= $amount;
                $combinedRunning += $amount;
                $debit = $amount;
                $credit = null;
            }

            $rows[] = $this->fileLedgerTableRow(
                date: $entry->entry_date,
                voucher: $entry->getVoucherNumber(),
                party: $candidate['party_name'],
                paymentMethod: $this->fileLedgerPaymentMethodLabel($entry) ?: '—',
                paymentMethodLines: $entry->ledgerPaymentDetailLines(),
                debit: $debit,
                credit: $credit,
                runningBalance: $combinedRunning,
            );
        }

        return [
            'title' => $subCategoryName,
            'subtitle' => 'All parties · '.$categoryName,
            'rows' => $rows,
            'footer' => [
                'label' => 'Combined balance payable',
                'debit' => null,
                'credit' => $totalPaid > 0 ? $totalPaid : null,
                'running_balance' => $combinedRunning,
            ],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, DayBookEntry>  $entries
     * @return array<string, mixed>
     */
    private function buildFileLedgerExpenseSection(
        string $title,
        ?string $subtitle,
        $entries,
        ?\Carbon\CarbonInterface $fallbackDate = null,
    ): array {
        $entries = $entries->sortBy(fn (DayBookEntry $e) => [$e->entry_date->toDateString(), $e->id])->values();
        $totalAmount = $this->daybookEntriesNetPaid($entries);
        $firstEntry = $entries->first();
        $openingDate = $firstEntry?->entry_date ?? $fallbackDate;
        $openingVoucher = $firstEntry ? $firstEntry->getVoucherNumber() : null;
        $paidRunning = 0.0;
        $rows = [];

        if ($totalAmount > 0) {
            $openingParty = $title;
            if ($subtitle) {
                $openingParty .= ' ('.$subtitle.')';
            }

            $rows[] = $this->fileLedgerTableRow(
                date: $openingDate,
                voucher: $openingVoucher,
                party: $openingParty,
                paymentMethod: '—',
                debit: $totalAmount,
                credit: null,
                runningBalance: $totalAmount,
                isOpening: true,
            );
        }

        foreach ($entries as $entry) {
            $amount = (float) $entry->amount;
            if ($entry->type === DayBookEntry::TYPE_CASH_OUT) {
                $paidRunning += $amount;
                $debit = null;
                $credit = $amount;
            } else {
                $paidRunning -= $amount;
                $debit = $amount;
                $credit = null;
            }

            $rows[] = $this->fileLedgerTableRow(
                date: $entry->entry_date,
                voucher: $entry->getVoucherNumber(),
                party: $title,
                paymentMethod: $this->fileLedgerPaymentMethodLabel($entry) ?: '—',
                paymentMethodLines: $entry->ledgerPaymentDetailLines(),
                debit: $debit,
                credit: $credit,
                runningBalance: max(0.0, $totalAmount - $paidRunning),
            );
        }

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'rows' => $rows,
            'footer' => [
                'label' => 'Balance Payable',
                'debit' => null,
                'credit' => $paidRunning > 0 ? $paidRunning : null,
                'running_balance' => max(0.0, $totalAmount - $paidRunning),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fileLedgerTableRow(
        ?\Carbon\CarbonInterface $date,
        ?string $voucher,
        string $party,
        string $paymentMethod,
        ?float $debit,
        ?float $credit,
        ?float $runningBalance,
        bool $isOpening = false,
        array $paymentMethodLines = [],
    ): array {
        return [
            'date' => $date ? $date->format('d-M-Y') : '—',
            'voucher' => $voucher ?: '—',
            'party' => $party,
            'payment_method' => $paymentMethod,
            'payment_method_lines' => $paymentMethodLines,
            'debit' => $debit,
            'credit' => $credit,
            'running_balance' => $runningBalance,
            'is_opening' => $isOpening,
        ];
    }

    private function fileLedgerPaymentMethodLabel(DayBookEntry $entry): string
    {
        return match ($entry->payment_method) {
            DayBookEntry::PAYMENT_CASH => 'Cash',
            DayBookEntry::PAYMENT_ONLINE => 'Online Transfer',
            DayBookEntry::PAYMENT_CHEQUE => 'Cheque',
            DayBookEntry::PAYMENT_PAYORDER => 'Pay Order',
            null, '' => $entry->type === DayBookEntry::TYPE_CASH_IN ? '' : 'Cash',
            default => ucfirst(str_replace('_', ' ', (string) $entry->payment_method)),
        };
    }

    private function fileLedgerPartySubtitle(?Party $party, float $landTotal, float $commission): ?string
    {
        $roles = [];
        if ($landTotal > 0) {
            $roles[] = 'Seller';
        }
        if ($commission > 0) {
            $roles[] = 'Dealer';
        }
        if ($party && $party->relationLoaded('subCategory') && $party->subCategory) {
            $cat = $party->subCategory->relationLoaded('category') && $party->subCategory->category
                ? $party->subCategory->category->name
                : null;
            $sub = $party->subCategory->name;
            $roles[] = $cat ? ($cat.' — '.$sub) : $sub;
        }

        return $roles !== [] ? implode(' · ', $roles) : null;
    }

    private function fileLedgerPartyMeta(?Party $party, float $landTotal, float $commission): string
    {
        $parts = [];
        if ($landTotal > 0) {
            $parts[] = 'Seller';
        }
        if ($commission > 0) {
            $parts[] = 'Dealer';
        }
        if ($parts === [] && $party && $party->relationLoaded('category') && $party->category) {
            $parts[] = $party->category->name;
        }

        return $parts !== [] ? implode(' · ', $parts) : 'Party';
    }

    /**
     * @param  \Illuminate\Support\Collection<int, DayBookEntry>  $entries
     */
    private function daybookEntriesNetPaid($entries): float
    {
        $out = (float) $entries->where('type', DayBookEntry::TYPE_CASH_OUT)->sum('amount');
        $in = (float) $entries->where('type', DayBookEntry::TYPE_CASH_IN)->sum('amount');

        return $out - $in;
    }

    /**
     * @param  \Illuminate\Support\Collection<int|string, Party>  $parties
     */
    private function daybookEntryPartyName(DayBookEntry $entry, $parties): string
    {
        if ($entry->link_type === DayBookEntry::LINK_PARTY && $entry->link_id) {
            return $parties->get((int) $entry->link_id)?->name ?? ('Party #'.$entry->link_id);
        }

        return 'General';
    }

    private function daybookEntryPaymentText(DayBookEntry $entry): string
    {
        $kind = $entry->type === DayBookEntry::TYPE_CASH_IN ? 'Payment in' : 'Payment out';
        $settlement = $entry->getSettlementLabel();
        if ($settlement !== '' && $settlement !== '—') {
            return $kind.' · '.$settlement;
        }

        return $kind;
    }

    private function daybookEntryDescription(DayBookEntry $entry): string
    {
        $descParts = [];
        if ($entry->description && trim((string) $entry->description) !== '') {
            $descParts[] = trim((string) $entry->description);
        }
        if ($entry->voucher_no) {
            $descParts[] = 'Voucher '.$entry->getVoucherNumber();
        }

        return $descParts !== [] ? implode(' · ', $descParts) : '—';
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
            'documents.*' => ['file', 'max:51200'],
        ], [
            'documents.*.max' => 'Each file must be 50 MB or smaller.',
        ]);

        $created = [];
        foreach ($request->file('documents') as $file) {
            $doc = $purchase_file->queueDocument($file);
            $created[] = $this->documentPayload($doc);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => count($created).' file(s) received. Processing in the background.',
                'documents' => $created,
            ]);
        }

        return redirect()
            ->route('purchase.files.documents', $purchase_file)
            ->with('success', count($created).' file(s) received. Processing in the background.');
    }

    public function destroyDocument(PurchaseFile $purchase_file, int $document)
    {
        $doc = $purchase_file->documents()->findOrFail($document);
        $doc->delete();

        return redirect()
            ->route('purchase.files.documents', $purchase_file)
            ->with('success', 'Document removed.');
    }

    public function documentStatus(PurchaseFile $purchase_file, int $document)
    {
        $doc = $purchase_file->documents()->findOrFail($document);

        return response()->json($this->documentPayload($doc));
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
        $disk = $doc->storageDisk();
        $bytes = $doc->file_path && Storage::disk($disk)->exists($doc->file_path)
            ? (int) Storage::disk($disk)->size($doc->file_path)
            : 0;

        return [
            'id' => $doc->id,
            'name' => $doc->name,
            'url' => $doc->isProcessed() ? asset('storage/'.$doc->file_path) : null,
            'size_label' => $this->formatBytes($bytes),
            'created_at' => $doc->created_at?->format('d M Y, H:i') ?? '—',
            'status' => $doc->status,
            'status_label' => $this->documentStatusLabel($doc),
            'is_processing' => $doc->isProcessing(),
        ];
    }

    private function documentStatusLabel(PurchaseFileDocument $doc): string
    {
        return match ($doc->status) {
            PurchaseFileDocument::STATUS_PENDING => 'Queued',
            PurchaseFileDocument::STATUS_PROCESSING => 'Processing',
            PurchaseFileDocument::STATUS_FAILED => 'Failed',
            default => 'Ready',
        };
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
