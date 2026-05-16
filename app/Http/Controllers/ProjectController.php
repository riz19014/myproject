<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DayBookEntry;
use App\Models\LandType;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Support\LandMeasure;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $projects = Project::query()
            ->with('landType')
            ->withCount('purchaseFiles')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhereHas('landType', fn ($lt) => $lt->where('name', 'like', $like));
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('projects.index', compact('projects', 'search'));
    }

    public function saleIndex()
    {
        return $this->typedIndex('sale');
    }

    public function purchaseIndex()
    {
        return $this->typedIndex('purchase');
    }

    private function typedIndex(string $type)
    {
        abort_unless(in_array($type, ['sale', 'purchase'], true), 404);

        $projects = Project::query()
            ->with(['landType', 'parties'])
            ->withCount($type === 'purchase' ? 'purchaseFiles' : 'projectFiles')
            ->orderBy('id', 'desc')
            ->get();

        $totalAmount = $type === 'sale'
            ? (float) Sale::query()->sum('total_amount')
            : (float) PurchaseItem::query()->sum('line_total_rs');
        $byLandType = $projects->groupBy(fn (Project $p) => $p->land_type_id ?: 0);

        $sales = collect();
        if ($type === 'sale') {
            $sales = Sale::query()
                ->with(['project', 'participants.party', 'participants.customer', 'landCuttings'])
                ->orderByDesc('id')
                ->limit(300)
                ->get();
        }

        $purchaseItems = collect();
        if ($type === 'purchase') {
            $purchaseItems = PurchaseItem::query()
                ->with(['project', 'party', 'purchaseFile.dealers'])
                ->orderByDesc('id')
                ->limit(400)
                ->get();
        }

        return view('projects.typed-index', [
            'type' => $type,
            'projects' => $projects,
            'totalAmount' => $totalAmount,
            'byLandType' => $byLandType,
            'sales' => $sales,
            'purchaseItems' => $purchaseItems,
        ]);
    }

    public function create(Request $request)
    {
        $landTypes = LandType::orderBy('name')->get();

        $context = $request->query('context');
        if ($context !== null && $context !== '' && ! in_array($context, ['sale', 'purchase'], true)) {
            abort(404);
        }
        $context = in_array($context, ['sale', 'purchase'], true) ? $context : null;

        return view('projects.create', compact('landTypes', 'context'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'land_type_id' => ['nullable', 'integer', 'exists:land_types,id'],
        ]);

        $project = Project::create($validated);

        $context = $request->input('context');
        if (in_array($context, ['sale', 'purchase'], true)) {
            return redirect()->route($context.'.index')
                ->with('success', 'Project created successfully.');
        }

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    /**
     * Create a project from the daybook page; returns JSON so the new option can be selected immediately.
     */
    public function quickStore(Request $request)
    {
        if ($request->boolean('simple')) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'land_type_id' => ['required', 'integer', 'exists:land_types,id'],
            ]);

            $project = Project::create([
                'name' => $validated['name'],
                'land_type_id' => $validated['land_type_id'],
            ]);
            $project->load('parties');

            return response()->json(array_merge([
                'id' => $project->id,
                'name' => $project->name,
            ], LandMeasure::projectPartyAreaPayload($project)));
        }

        $partyIds = collect($request->input('party_ids', []))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (count($partyIds) === 0) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'area_acre' => ['required', 'integer', 'min:0'],
                'area_kanal' => ['required', 'integer', 'min:0'],
                'area_marla' => ['required', 'integer', 'min:0'],
                'area_sqft' => ['required', 'integer', 'min:0'],
                'land_type_id' => ['required', 'integer', 'exists:land_types,id'],
            ]);

            $marlaTotal = LandMeasure::marlaFromAkms(
                (int) $validated['area_acre'],
                (int) $validated['area_kanal'],
                (int) $validated['area_marla'],
                (int) $validated['area_sqft']
            );
            if ($marlaTotal <= 0) {
                throw ValidationException::withMessages([
                    'area_acre' => ['Enter at least one positive whole number in A, K, M, or SQFT.'],
                ]);
            }

            $project = Project::create([
                'name' => $validated['name'],
                'land_type_id' => $validated['land_type_id'],
            ]);
            $project->load('parties');

            return response()->json(array_merge([
                'id' => $project->id,
                'name' => $project->name,
            ], LandMeasure::projectPartyAreaPayload($project)));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'land_type_id' => ['required', 'integer', 'exists:land_types,id'],
            'party_ids' => ['required', 'array', 'min:1'],
            'party_ids.*' => ['integer', 'distinct', 'exists:parties,id'],
            'party_areas' => ['required', 'array', 'min:1'],
            'party_areas.*.party_id' => ['required', 'integer', 'exists:parties,id'],
            'party_areas.*.area_acre' => ['required', 'integer', 'min:0'],
            'party_areas.*.area_kanal' => ['required', 'integer', 'min:0'],
            'party_areas.*.area_marla' => ['required', 'integer', 'min:0'],
            'party_areas.*.area_sqft' => ['required', 'integer', 'min:0'],
        ]);

        $partyIds = collect($validated['party_ids'])->map(fn ($id) => (int) $id)->unique()->values()->all();

        $byParty = [];
        $seen = [];
        foreach ($validated['party_areas'] as $row) {
            $pid = (int) $row['party_id'];
            if (isset($seen[$pid])) {
                throw ValidationException::withMessages([
                    'party_areas' => ['Each party may only appear once in the area list.'],
                ]);
            }
            $seen[$pid] = true;
            $marlaParty = LandMeasure::marlaFromAkms(
                (int) $row['area_acre'],
                (int) $row['area_kanal'],
                (int) $row['area_marla'],
                (int) $row['area_sqft']
            );
            if ($marlaParty <= 0) {
                throw ValidationException::withMessages([
                    'party_areas' => ['Each party needs at least one positive whole number in A, K, M, or SQFT.'],
                ]);
            }
            $byParty[$pid] = [
                'land_area' => round($marlaParty, 4),
                'land_area_unit' => 'marla',
            ];
        }

        foreach ($partyIds as $pid) {
            if (! isset($byParty[$pid])) {
                throw ValidationException::withMessages([
                    'party_areas' => ['Each selected party must have an area (A, K, M, SQFT).'],
                ]);
            }
        }

        foreach (array_keys($byParty) as $pid) {
            if (! in_array($pid, $partyIds, true)) {
                throw ValidationException::withMessages([
                    'party_areas' => ['Party areas must match selected parties only.'],
                ]);
            }
        }

        $totalMarla = 0.0;
        foreach ($partyIds as $pid) {
            $totalMarla += (float) $byParty[$pid]['land_area'];
        }

        $project = Project::create([
            'name' => $validated['name'],
            'land_type_id' => $validated['land_type_id'],
        ]);

        $sync = [];
        foreach ($partyIds as $pid) {
            $sync[$pid] = [
                'land_area' => $byParty[$pid]['land_area'],
                'land_area_unit' => $byParty[$pid]['land_area_unit'],
            ];
        }
        $project->parties()->sync($sync);

        $project->load('parties');

        return response()->json(array_merge([
            'id' => $project->id,
            'name' => $project->name,
        ], LandMeasure::projectPartyAreaPayload($project)));
    }

    public function show(Project $project)
    {
        $project->load([
            'purchaseFiles' => fn ($q) => $q->with(['purchaseItems.party'])->orderByDesc('file_date')->orderBy('file_name'),
            'landType',
        ]);
        $ledger = $this->buildProjectLedger($project);

        return view('projects.show', array_merge(compact('project'), $ledger));
    }

    /**
     * All daybook lines tied to this project (direct project link or party + project context).
     */
    private function projectDayBookEntries(Project $project)
    {
        return DayBookEntry::query()
            ->linkedToProject($project)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{entries: \Illuminate\Support\Collection, sections: array<int, array>, totalIn: float, totalOut: float, netFlow: float}
     */
    private function buildProjectLedger(Project $project): array
    {
        $entries = $this->projectDayBookEntries($project);

        $partyIds = $entries->where('link_type', DayBookEntry::LINK_PARTY)->pluck('link_id')->unique()->filter()->values();
        $parties = Party::query()
            ->with(['subCategory.category'])
            ->whereIn('id', $partyIds)
            ->get()
            ->keyBy('id');

        $general = $entries->filter(fn (DayBookEntry $e) => $e->link_type !== DayBookEntry::LINK_PARTY)->values();

        $byParty = $entries->where('link_type', DayBookEntry::LINK_PARTY)->groupBy('link_id');

        $sections = [];
        foreach ($byParty as $partyId => $rows) {
            $rows = $rows->sortBy(fn (DayBookEntry $e) => [$e->entry_date->toDateString(), $e->id])->values();
            $party = $parties->get((int) $partyId);
            $running = 0.0;
            $lines = [];
            foreach ($rows as $e) {
                if ($e->type === DayBookEntry::TYPE_CASH_IN) {
                    $running += (float) $e->amount;
                } else {
                    $running -= (float) $e->amount;
                }
                $lines[] = ['entry' => $e, 'balance' => $running];
            }
            $subtitle = null;
            if ($party && $party->relationLoaded('subCategory') && $party->subCategory) {
                $catName = $party->subCategory->relationLoaded('category') && $party->subCategory->category
                    ? $party->subCategory->category->name
                    : '';
                $subtitle = trim($catName.' — '.$party->subCategory->name);
            }
            $sections[] = [
                'key' => 'party_'.$partyId,
                'heading' => $party ? $party->name : 'Party #'.$partyId,
                'subtitle' => $subtitle,
                'lines' => $lines,
                'net' => $running,
            ];
        }

        usort($sections, fn ($a, $b) => strcasecmp($a['heading'], $b['heading']));

        if ($general->isNotEmpty()) {
            $rows = $general->sortBy(fn (DayBookEntry $e) => [$e->entry_date->toDateString(), $e->id])->values();
            $running = 0.0;
            $lines = [];
            foreach ($rows as $e) {
                if ($e->type === DayBookEntry::TYPE_CASH_IN) {
                    $running += (float) $e->amount;
                } else {
                    $running -= (float) $e->amount;
                }
                $lines[] = ['entry' => $e, 'balance' => $running];
            }
            array_unshift($sections, [
                'key' => 'general',
                'heading' => 'General (project only)',
                'subtitle' => 'Payments linked to this project without a party',
                'lines' => $lines,
                'net' => $running,
            ]);
        }

        $totalIn = (float) $entries->where('type', DayBookEntry::TYPE_CASH_IN)->sum('amount');
        $totalOut = (float) $entries->where('type', DayBookEntry::TYPE_CASH_OUT)->sum('amount');

        return [
            'entries' => $entries,
            'ledgerSections' => $sections,
            'ledgerTotalIn' => $totalIn,
            'ledgerTotalOut' => $totalOut,
            'ledgerNetFlow' => $totalIn - $totalOut,
        ];
    }

    /**
     * Project land as A — K — M — SQFT for PDF header, or em dash when not set.
     */
    private function projectLedgerLandAkmsLine(Project $project): string
    {
        $project->loadMissing('parties');
        $marla = LandMeasure::partiesTotalMarla($project->parties);
        if ($marla <= 0) {
            return '—';
        }

        return LandMeasure::formatAkmsLabelFromMarla($marla);
    }

    /**
     * All project-linked daybook lines in one chronological list with global running balance.
     *
     * @return list<array{date: string, party: string, payment: string, amount: float, amount_in: bool, running: float}>
     */
    private function buildProjectLedgerFlatRows(Project $project): array
    {
        $entries = $this->projectDayBookEntries($project)
            ->sortBy(fn (DayBookEntry $e) => [$e->entry_date->toDateString(), $e->id])
            ->values();

        $partyIds = $entries->where('link_type', DayBookEntry::LINK_PARTY)->pluck('link_id')->unique()->filter()->values();
        $parties = Party::query()->whereIn('id', $partyIds)->get()->keyBy('id');

        $running = 0.0;
        $rows = [];
        foreach ($entries as $e) {
            if ($e->type === DayBookEntry::TYPE_CASH_IN) {
                $running += (float) $e->amount;
            } else {
                $running -= (float) $e->amount;
            }

            $partyName = 'General';
            if ($e->link_type === DayBookEntry::LINK_PARTY && $e->link_id) {
                $partyName = $parties->get((int) $e->link_id)?->name ?? ('Party #'.$e->link_id);
            }

            $kind = $e->type === DayBookEntry::TYPE_CASH_IN ? 'Payment in' : 'Payment out';
            $settlement = $e->getSettlementLabel();
            $paymentText = $kind;
            if ($settlement !== '' && $settlement !== '—') {
                $paymentText .= ' · '.$settlement;
            }

            $rows[] = [
                'date' => $e->entry_date->format('d M Y'),
                'party' => $partyName,
                'payment' => $paymentText,
                'amount' => (float) $e->amount,
                'amount_in' => $e->type === DayBookEntry::TYPE_CASH_IN,
                'running' => $running,
            ];
        }

        return $rows;
    }

    public function ledgerPdf(Project $project)
    {
        $project->loadMissing('landType');
        $ledger = $this->buildProjectLedger($project);
        $generatedAt = now();
        $ledgerFlatRows = $this->buildProjectLedgerFlatRows($project);
        $projectLandAkms = $this->projectLedgerLandAkmsLine($project);

        $pdf = Pdf::loadView('projects.ledger-pdf', array_merge(
            compact('project', 'generatedAt', 'ledgerFlatRows', 'projectLandAkms'),
            [
                'entryCount' => $ledger['entries']->count(),
            ]
        ));
        $pdf->setPaper('a4', 'portrait');

        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $project->name);

        return $pdf->download('project-ledger-'.$safeName.'-'.now()->format('Y-m-d').'.pdf');
    }

    public function edit(Project $project)
    {
        $landTypes = LandType::orderBy('name')->get();

        return view('projects.edit', compact('project', 'landTypes'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'land_type_id' => ['nullable', 'integer', 'exists:land_types,id'],
        ]);
        $project->update($validated);

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }

    // Add file to project (e.g. 50 files from DHA)
    public function addFile(Request $request, Project $project)
    {
        $rules = [
            'file_number' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'dealer_party_id' => ['nullable', 'integer', 'exists:parties,id'],
        ];

        $validated = $request->validate($rules);

        $data = [
            'file_number' => $validated['file_number'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'available',
            'dealer_party_id' => $validated['dealer_party_id'] ?? null,
        ];

        $project->projectFiles()->create($data);

        return redirect()->route('projects.show', $project)->with('success', 'File added to project.');
    }

    // Sell file to customer
    public function sellFile(Request $request, Project $project, ProjectFile $projectFile)
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'sale_amount' => ['nullable', 'numeric', 'min:0'],
            'sale_date' => ['nullable', 'date'],
        ]);
        $projectFile->update([
            'status' => 'sold',
            'customer_id' => $validated['customer_id'],
            'sale_amount' => $validated['sale_amount'] ?? null,
            'sale_date' => $validated['sale_date'] ?? now(),
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'File marked as sold.');
    }

    // Upload document for a project file
    public function uploadFileDocument(Request $request, Project $project, ProjectFile $projectFile)
    {
        $request->validate(['documents' => 'required', 'documents.*' => 'file|max:10240']);
        foreach ($request->file('documents') as $file) {
            $projectFile->addDocument($file);
        }

        return redirect()->route('projects.show', $project)->with('success', 'Document(s) uploaded.');
    }

    public function destroyFileDocument(Project $project, ProjectFile $projectFile, int $document)
    {
        $doc = $projectFile->documents()->findOrFail($document);
        $doc->delete();

        return redirect()->route('projects.show', $project)->with('success', 'Document removed.');
    }
}
