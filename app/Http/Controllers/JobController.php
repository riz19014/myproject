<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    /**
     * Display a listing of the jobs.
     */
    public function index()
    {
        $jobs = Job::orderBy('id', 'desc')->paginate(10);
        return view('jobs.index', compact('jobs'));
    }

    /**
     * Show the form for creating a new job.
     */
    public function create()
    {
        return view('jobs.create');
    }

    /**
     * Store a newly created job in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'no_of_positions' => ['required', 'integer', 'min:1'],
            'salary_range' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'in:onsite,remote'],
            'job_type' => ['required', 'in:permanent,contract,part_time,full_time'],
        ]);

        Job::create($validated);

        return redirect()->route('jobs.index')
            ->with('success', 'Job created successfully.');
    }

    /**
     * Display the specified job.
     */
    public function show(Job $job)
    {
        return view('jobs.show', compact('job'));
    }

    /**
     * Show the form for editing the specified job.
     */
    public function edit(Job $job)
    {
        return view('jobs.edit', compact('job'));
    }

    /**
     * Update the specified job in storage.
     */
    public function update(Request $request, Job $job)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'no_of_positions' => ['required', 'integer', 'min:1'],
            'salary_range' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'in:onsite,remote'],
            'job_type' => ['required', 'in:permanent,contract,part_time,full_time'],
        ]);

        $job->update($validated);

        return redirect()->route('jobs.index')
            ->with('success', 'Job updated successfully.');
    }

    /**
     * Remove the specified job from storage.
     */
    public function destroy(Job $job)
    {
        $job->delete();

        return redirect()->route('jobs.index')
            ->with('success', 'Job deleted successfully.');
    }
}
