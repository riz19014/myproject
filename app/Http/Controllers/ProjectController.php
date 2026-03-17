<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DayBookEntry;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::withCount('projectFiles')->orderBy('id', 'desc')->paginate(10);
        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
        Project::create($validated);
        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $project->load(['projectFiles.customer', 'projectFiles.documents']);
        $payments = DayBookEntry::where('link_type', 'project')->where('link_id', $project->id)->orderBy('entry_date', 'desc')->get();
        return view('projects.show', compact('project', 'payments'));
    }

    public function edit(Project $project)
    {
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
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
