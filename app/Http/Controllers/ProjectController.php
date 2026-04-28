<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DayBookEntry;
use App\Models\LandType;
use App\Models\Party;
use App\Models\Project;
use App\Models\ProjectFile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::withCount('projectFiles')->orderBy('id', 'desc')->paginate(10);
        return view('projects.index', compact('projects'));
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
            ->with(['landType'])
            ->withCount('projectFiles')
            ->where('field_type', $type)
            ->orderBy('id', 'desc')
            ->get();

        $totalAmount = (float) $projects->sum('total_amount');
        $byLandType = $projects->groupBy(fn (Project $p) => $p->land_type_id ?: 0);

        return view('projects.typed-index', [
            'type' => $type,
            'projects' => $projects,
            'totalAmount' => $totalAmount,
            'byLandType' => $byLandType,
        ]);
    }

    public function create()
    {
        $landTypes = LandType::orderBy('name')->get();

        return view('projects.create', compact('landTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'land_area' => ['nullable', 'numeric', 'min:0'],
            'land_area_unit' => ['nullable', 'string', Rule::in(['acre', 'kanal', 'marla', 'sqft'])],
            'field_type' => ['nullable', 'string', Rule::in(['sale', 'purchase'])],
            'land_type_id' => ['nullable', 'integer', 'exists:land_types,id'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
        Project::create($validated);
        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    /**
     * Create a project from the daybook page; returns JSON so the new option can be selected immediately.
     */
    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'land_area' => ['required', 'integer', 'min:0'],
            'land_area_unit' => ['required', 'string', Rule::in(['acre', 'kanal', 'marla', 'sqft'])],
            'field_type' => ['required', 'string', Rule::in(['sale', 'purchase'])],
            'land_type_id' => ['required', 'integer', 'exists:land_types,id'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'party_ids' => ['nullable', 'array'],
            'party_ids.*' => ['integer', 'exists:parties,id'],
        ]);
        $project = Project::create([
            'name' => $validated['name'],
            'land_area' => $validated['land_area'],
            'land_area_unit' => $validated['land_area_unit'],
            'field_type' => $validated['field_type'],
            'total_amount' => $validated['total_amount'],
            'land_type_id' => $validated['land_type_id'],
            'description' => null,
            'notes' => null,
        ]);

        $partyIds = collect($validated['party_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        if (!empty($partyIds)) {
            $project->parties()->sync($partyIds);
        }

        return response()->json([
            'id' => $project->id,
            'name' => $project->name,
        ]);
    }

    public function show(Project $project)
    {
        $project->load(['projectFiles.customer', 'projectFiles.documents', 'landType']);
        $ledger = $this->buildProjectLedger($project);

        return view('projects.show', array_merge(compact('project'), $ledger));
    }

    /**
     * All daybook lines tied to this project (direct project link or party + project context).
     */
    private function projectDayBookEntries(Project $project)
    {
        return DayBookEntry::query()
            ->where(function ($q) use ($project) {
                $q->where('project_id', $project->id)
                    ->orWhere(function ($q2) use ($project) {
                        $q2->where('link_type', DayBookEntry::LINK_PROJECT)
                            ->where('link_id', $project->id);
                    });
            })
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

    public function ledgerPdf(Project $project)
    {
        $ledger = $this->buildProjectLedger($project);
        $generatedAt = now();

        $pdf = Pdf::loadView('projects.ledger-pdf', array_merge(
            compact('project', 'generatedAt'),
            [
                'ledgerSections' => $ledger['ledgerSections'],
                'ledgerTotalIn' => $ledger['ledgerTotalIn'],
                'ledgerTotalOut' => $ledger['ledgerTotalOut'],
                'ledgerNetFlow' => $ledger['ledgerNetFlow'],
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
            'land_area' => ['nullable', 'numeric', 'min:0'],
            'land_area_unit' => ['nullable', 'string', Rule::in(['acre', 'kanal', 'marla', 'sqft'])],
            'field_type' => ['nullable', 'string', Rule::in(['sale', 'purchase'])],
            'land_type_id' => ['nullable', 'integer', 'exists:land_types,id'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
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
        $validated = $request->validate([
            'file_number' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
        $project->projectFiles()->create(array_merge($validated, ['status' => 'available']));
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
