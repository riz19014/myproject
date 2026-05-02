<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::query()->orderBy('code')->get();

        $ym = Setting::getValue(Setting::CODE_DAYBOOK_DEFAULT_MONTH_YEAR);
        $year = null;
        $month = null;
        if ($ym && preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
        }

        return view('settings.index', [
            'settings' => $settings,
            'daybookDefaultYear' => $year,
            'daybookDefaultMonth' => $month,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'daybook_default_year' => ['nullable', 'integer', 'min:1970', 'max:2100'],
            'daybook_default_month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $y = $validated['daybook_default_year'] ?? null;
        $mo = $validated['daybook_default_month'] ?? null;

        $hasY = $y !== null && $y !== '';
        $hasM = $mo !== null && $mo !== '';

        if (! $hasY && ! $hasM) {
            Setting::put(Setting::CODE_DAYBOOK_DEFAULT_MONTH_YEAR, null);

            return redirect()->route('settings.index')->with('success', 'Daybook default cleared. Daybook will open on today’s date when no date is chosen.');
        }

        if ($hasY xor $hasM) {
            return back()
                ->withErrors([
                    'daybook_default_month' => 'Choose both month and year, or leave both empty for today’s date.',
                ])
                ->withInput();
        }

        $value = sprintf('%04d-%02d', (int) $y, (int) $mo);
        Setting::put(Setting::CODE_DAYBOOK_DEFAULT_MONTH_YEAR, $value);

        return redirect()->route('settings.index')->with('success', 'Settings saved.');
    }
}
