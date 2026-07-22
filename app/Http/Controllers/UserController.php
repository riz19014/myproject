<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\CnicFormat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $users = User::orderBy('id', 'desc')->paginate(10);

        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('users.create', [
            'userTypes' => User::typeLabels(),
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $this->normalizeUserContactInput($request);

        $validated = $request->validate(
            $this->userFieldRules(),
            $this->userFieldMessages()
        );

        $validated['is_active'] = $request->boolean('is_active');
        $validated['phone'] = $validated['phone'] ?? null;
        $validated['cnic'] = isset($validated['cnic']) && $validated['cnic'] !== ''
            ? CnicFormat::digits($validated['cnic'])
            : null;
        $validated['type'] = $validated['type'] ?? User::TYPE_ACCOUNTANT;

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'userTypes' => User::typeLabels(),
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->normalizeUserContactInput($request);

        $validated = $request->validate(
            $this->userFieldRules($user),
            $this->userFieldMessages()
        );

        $validated['is_active'] = $request->boolean('is_active');
        $validated['phone'] = $validated['phone'] ?? null;
        $validated['cnic'] = isset($validated['cnic']) && $validated['cnic'] !== ''
            ? CnicFormat::digits($validated['cnic'])
            : null;

        if (! empty($validated['password'])) {
            $validated['password'] = $validated['password'];
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    private function normalizeUserContactInput(Request $request): void
    {
        if ($request->has('phone')) {
            $phone = $request->input('phone');
            $request->merge([
                'phone' => $phone !== null && $phone !== ''
                    ? preg_replace('/\D/', '', (string) $phone)
                    : null,
            ]);
        }

        if ($request->has('cnic')) {
            $cnic = $request->input('cnic');
            $request->merge([
                'cnic' => $cnic !== null && $cnic !== ''
                    ? CnicFormat::digits((string) $cnic)
                    : null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function userFieldRules(?User $user = null): array
    {
        $emailRule = ['required', 'string', 'email', 'max:255'];
        $emailRule[] = $user
            ? 'unique:users,email,'.$user->id
            : 'unique:users,email';

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRule,
            'phone' => ['nullable', 'string', 'regex:/^\d{11}$/'],
            'cnic' => ['nullable', 'string', 'regex:/^\d{13}$/'],
            'type' => ['required', 'string', Rule::in(User::types())],
            'password' => $user
                ? ['nullable', 'string', Password::defaults()]
                : ['required', 'string', Password::defaults()],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function userFieldMessages(): array
    {
        return [
            'phone.regex' => 'Phone must be exactly 11 digits (numbers only).',
            'cnic.regex' => 'CNIC must be 13 digits in format 23012-2321373-1.',
        ];
    }
}
