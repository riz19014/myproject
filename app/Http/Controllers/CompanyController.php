<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::query()
            ->orderBy('name')
            ->paginate(15);

        return view('company.index', compact('companies'));
    }

    public function create()
    {
        return view('company.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedDetails($request, required: true);
        $this->validateLogo($request);

        $company = Company::create($validated);

        if ($request->hasFile('logo')) {
            $company->storeLogo($request->file('logo'));
        }

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company added.');
    }

    public function edit(Company $company)
    {
        return view('company.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $validated = $this->validatedDetails($request, required: true);

        $company->update($validated);

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company updated.');
    }

    public function showLogo(Company $company)
    {
        if (! $company->logo_path || ! Storage::disk('public')->exists($company->logo_path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($company->logo_path));
    }

    public function storeLogo(Request $request, Company $company)
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ], [
            'logo.required' => 'Please choose a logo file.',
            'logo.max' => 'Logo must be 2 MB or smaller.',
            'logo.image' => 'Logo must be an image file.',
        ]);

        $company->storeLogo($request->file('logo'));
        $company->refresh();

        return response()->json([
            'message' => 'Company logo uploaded.',
            'logo_url' => $company->logo_url,
            'logo_path' => $company->logo_path,
        ]);
    }

    public function destroyLogo(Company $company)
    {
        $company->deleteLogo();

        return response()->json([
            'message' => 'Company logo removed.',
            'logo_url' => null,
            'logo_path' => null,
        ]);
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedDetails(Request $request, bool $required): array
    {
        return $request->validate([
            'name' => [$required ? 'required' : 'nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Company name is required.',
        ]);
    }

    private function validateLogo(Request $request): void
    {
        if (! $request->hasFile('logo')) {
            return;
        }

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
        ], [
            'logo.max' => 'Logo must be 2 MB or smaller.',
            'logo.image' => 'Logo must be an image file.',
        ]);
    }
}
