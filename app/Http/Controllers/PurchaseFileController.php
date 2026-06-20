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
            'dealers',
            'purchaseItems' => fn ($q) => $q->with('party')->orderBy('id'),
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
            ->with(['partySubCategory.category'])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $partyIds = $allEntries->where('link_type', DayBookEntry::LINK_PARTY)->pluck('link_id')->unique()->filter()->values();
        $parties = Party::query()->whereIn('id', $partyIds)->get()->keyBy('id');

        $paymentEntries = $allEntries->whereNull('party_sub_category_id')->values();
        $expenseEntries = $allEntries->whereNotNull('party_sub_category_id')->values();

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
     * @return array{ledgerNav: list<array<string, mixed>>, ledgerNavGrouped: array<string, list<array<string, mixed>>>, ledgerSections: array<string, array<string, mixed>>}
     */
    private function buildPurchaseFileLedger(PurchaseFile $file, array $data): array
    {
        $allEntries = DayBookEntry::query()
            ->where('purchase_file_id', $file->id)
            ->with(['partySubCategory.category'])
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
        foreach ($allEntries->whereNull('party_sub_category_id') as $entry) {
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

        $nav = [];
        $sections = [];

        foreach ($partyIds as $partyId) {
            $party = $parties->get($partyId);
            $landTotal = (float) $data['sellers']->where('party_id', $partyId)->sum('line_total_rs');
            $dealer = $file->dealers->firstWhere('id', $partyId);
            $commission = $dealer ? (float) ($dealer->pivot->commission_rs ?? 0) : 0.0;
            $openingAmount = $landTotal + $commission;

            $paymentEntries = $allEntries
                ->whereNull('party_sub_category_id')
                ->where('link_type', DayBookEntry::LINK_PARTY)
                ->where('link_id', $partyId)
                ->values();

            if ($openingAmount <= 0 && $paymentEntries->isEmpty()) {
                continue;
            }

            $key = 'party_'.$partyId;
            $sections[$key] = $this->buildFileLedgerPaymentSection(
                $party?->name ?? ('Party #'.$partyId),
                $this->fileLedgerPartySubtitle($party, $landTotal, $commission),
                $openingAmount,
                $this->fileLedgerOpeningDetails($landTotal, $commission),
                $paymentEntries,
                $parties
            );
            $nav[] = [
                'key' => $key,
                'group' => 'Parties',
                'label' => $party?->name ?? ('Party #'.$partyId),
                'meta' => $this->fileLedgerPartyMeta($party, $landTotal, $commission),
            ];
        }

        $generalPayments = $allEntries
            ->whereNull('party_sub_category_id')
            ->filter(fn (DayBookEntry $e) => $e->link_type !== DayBookEntry::LINK_PARTY || ! $e->link_id)
            ->values();
        if ($generalPayments->isNotEmpty()) {
            $key = 'general';
            $sections[$key] = $this->buildFileLedgerPaymentSection(
                'General',
                'Payments without a party on this file',
                0.0,
                null,
                $generalPayments,
                collect()
            );
            $nav[] = [
                'key' => $key,
                'group' => 'Parties',
                'label' => 'General',
                'meta' => 'Unlinked payments',
            ];
        }

        $categoryBuckets = [];
        foreach ($data['expenseGroups'] as $group) {
            $subKey = 'subcategory_'.$group['sub_category_id'];
            $sections[$subKey] = $this->buildFileLedgerExpenseSection(
                $group['sub_category'],
                $group['category'],
                $group['entries']
            );
            $nav[] = [
                'key' => $subKey,
                'group' => 'Subcategories',
                'label' => $group['sub_category'],
                'meta' => $group['category'],
            ];

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
                $entries
            );
            $nav[] = [
                'key' => $key,
                'group' => 'Categories',
                'label' => $bucket['name'],
                'meta' => $entries->count().' '.($entries->count() === 1 ? 'entry' : 'entries'),
            ];
        }

        usort($nav, function (array $a, array $b) {
            $groupOrder = ['Parties' => 0, 'Categories' => 1, 'Subcategories' => 2];
            $ga = $groupOrder[$a['group']] ?? 99;
            $gb = $groupOrder[$b['group']] ?? 99;
            if ($ga !== $gb) {
                return $ga <=> $gb;
            }

            return strcasecmp($a['label'], $b['label']);
        });

        $grouped = [];
        foreach ($nav as $item) {
            $grouped[$item['group']][] = $item;
        }

        return [
            'ledgerNav' => $nav,
            'ledgerNavGrouped' => $grouped,
            'ledgerSections' => $sections,
        ];
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
        ?string $openingDetails,
        $entries,
        $parties
    ): array {
        $rows = [];
        if ($openingAmount > 0) {
            $rows[] = [
                'details' => $openingDetails ?? 'Opening balance',
                'amount' => $openingAmount,
                'paid' => 0.0,
                'balance' => $openingAmount,
                'is_opening' => true,
            ];
        }

        $paidRunning = 0.0;
        foreach ($entries as $entry) {
            $amount = (float) $entry->amount;
            if ($entry->type === DayBookEntry::TYPE_CASH_OUT) {
                $paidRunning += $amount;
            } else {
                $paidRunning -= $amount;
            }
            $rows[] = [
                'details' => $this->fileLedgerEntryDetails($entry, $parties),
                'amount' => $amount,
                'paid' => $paidRunning,
                'balance' => $openingAmount > 0 ? $openingAmount - $paidRunning : null,
            ];
        }

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'rows' => $rows,
            'totals' => [
                'amount' => $openingAmount > 0 ? $openingAmount : $paidRunning,
                'paid' => $paidRunning,
                'balance' => $openingAmount > 0 ? $openingAmount - $paidRunning : null,
            ],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, DayBookEntry>  $entries
     * @return array<string, mixed>
     */
    private function buildFileLedgerExpenseSection(string $title, ?string $subtitle, $entries): array
    {
        $entries = $entries->sortBy(fn (DayBookEntry $e) => [$e->entry_date->toDateString(), $e->id])->values();
        $totalAmount = $this->daybookEntriesNetPaid($entries);
        $paidRunning = 0.0;
        $rows = [];

        foreach ($entries as $entry) {
            $amount = (float) $entry->amount;
            if ($entry->type === DayBookEntry::TYPE_CASH_OUT) {
                $paidRunning += $amount;
            } else {
                $paidRunning -= $amount;
            }
            $rows[] = [
                'details' => $this->fileLedgerEntryDetails($entry, collect()),
                'amount' => $amount,
                'paid' => $paidRunning,
                'balance' => max(0.0, $totalAmount - $paidRunning),
            ];
        }

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'rows' => $rows,
            'totals' => [
                'amount' => $totalAmount,
                'paid' => $paidRunning,
                'balance' => max(0.0, $totalAmount - $paidRunning),
            ],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int|string, Party>  $parties
     */
    private function fileLedgerEntryDetails(DayBookEntry $entry, $parties): string
    {
        $parts = [$entry->entry_date->format('d M Y')];
        $description = $this->daybookEntryDescription($entry);
        if ($description !== '—') {
            $parts[] = $description;
        }
        $parts[] = $this->daybookEntryPaymentText($entry);

        if ($entry->link_type === DayBookEntry::LINK_PARTY && $entry->link_id && $parties->isNotEmpty()) {
            $partyName = $parties->get((int) $entry->link_id)?->name;
            if ($partyName) {
                $parts[] = $partyName;
            }
        }

        return implode(' · ', $parts);
    }

    private function fileLedgerOpeningDetails(float $landTotal, float $commission): string
    {
        $parts = [];
        if ($landTotal > 0) {
            $parts[] = 'Land total (file)';
        }
        if ($commission > 0) {
            $parts[] = 'Commission';
        }

        return $parts !== [] ? implode(' · ', $parts) : 'Opening balance';
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
