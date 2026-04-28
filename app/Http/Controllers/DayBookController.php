<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DaybookOpeningBalance;
use App\Models\DayBookEntry;
use App\Models\Factory;
use App\Models\Land;
use App\Models\LandType;
use App\Models\Party;
use App\Models\PartySubCategory;
use App\Models\Plot;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DayBookController extends Controller
{
    /**
     * Closing balance for a calendar day: carried opening + petty cash + entries.
     */
    private function computeClosingForDate(Carbon $day): float
    {
        $dateStr = $day->toDateString();
        $rec = DaybookOpeningBalance::query()
            ->where('balance_date', $dateStr)
            ->first();
        $openingCarried = $rec ? (float) $rec->amount : 0.0;
        $petty = $rec ? (float) $rec->petty_cash : 0.0;

        $entries = DayBookEntry::query()
            ->whereDate('entry_date', $day)
            ->orderBy('id')
            ->get();

        $running = $openingCarried + $petty;
        foreach ($entries as $e) {
            if ($e->type === DayBookEntry::TYPE_CASH_IN) {
                $running += (float) $e->amount;
            } else {
                $running -= (float) $e->amount;
            }
        }

        return $running;
    }

    /**
     * True when the previous calendar day has ledger activity — then this day's carried opening must equal that day's closing.
     */
    private function shouldCarryOpeningFromPreviousDay(Carbon $day): bool
    {
        $prev = $day->copy()->subDay();
        $prevClosing = $this->computeClosingForDate($prev);
        $prevRec = DaybookOpeningBalance::query()
            ->where('balance_date', $prev->toDateString())
            ->first();
        $prevOpeningCarried = $prevRec ? (float) $prevRec->amount : 0.0;
        $prevPetty = $prevRec ? (float) $prevRec->petty_cash : 0.0;
        $prevHasEntries = DayBookEntry::query()->whereDate('entry_date', $prev)->exists();

        return $prevClosing != 0.0
            || $prevHasEntries
            || $prevOpeningCarried != 0.0
            || $prevPetty != 0.0;
    }

    /**
     * Next day's opening must match previous day's closing when the previous day has any ledger activity.
     * If the previous day is completely empty (no opening, no entries), keep this day's stored opening so a starting balance can be set once.
     */
    private function syncOpeningFromPreviousDay(Carbon $day): void
    {
        $dateStr = $day->toDateString();

        if ($this->shouldCarryOpeningFromPreviousDay($day)) {
            $prevClosing = $this->computeClosingForDate($day->copy()->subDay());
            DaybookOpeningBalance::updateOrCreate(
                ['balance_date' => $dateStr],
                ['amount' => $prevClosing]
            );
        } else {
            DaybookOpeningBalance::firstOrCreate(
                ['balance_date' => $dateStr],
                ['amount' => 0, 'petty_cash' => 0]
            );
        }
    }

    public function index(Request $request)
    {
        $day = $request->filled('date')
            ? Carbon::parse($request->date)->startOfDay()
            : Carbon::today();

        $dateStr = $day->toDateString();

        $this->syncOpeningFromPreviousDay($day);

        $entries = DayBookEntry::query()
            ->whereDate('entry_date', $day)
            ->orderBy('id')
            ->get();

        $openingRecord = DaybookOpeningBalance::query()
            ->where('balance_date', $dateStr)
            ->firstOrFail();
        $openingAmount = (float) $openingRecord->amount;
        $pettyCashAmount = (float) $openingRecord->petty_cash;

        $prevDay = $day->copy()->subDay();
        $previousDayClosing = $this->computeClosingForDate($prevDay);

        $cashIn = (float) DayBookEntry::query()
            ->whereDate('entry_date', $day)
            ->where('type', DayBookEntry::TYPE_CASH_IN)
            ->sum('amount');
        $cashOut = (float) DayBookEntry::query()
            ->whereDate('entry_date', $day)
            ->where('type', DayBookEntry::TYPE_CASH_OUT)
            ->sum('amount');

        $running = $openingAmount + $pettyCashAmount;
        foreach ($entries as $e) {
            if ($e->type === DayBookEntry::TYPE_CASH_IN) {
                $running += (float) $e->amount;
            } else {
                $running -= (float) $e->amount;
            }
        }
        $closingBalance = $running;

        $projects = Project::orderBy('name')->get();
        $parties = Party::orderBy('name')->get();
        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        $landTypes = LandType::orderBy('name')->get();

        return view('daybook.index', [
            'day' => $day,
            'prevDay' => $prevDay,
            'entries' => $entries,
            'openingRecord' => $openingRecord,
            'openingAmount' => $openingAmount,
            'pettyCashAmount' => $pettyCashAmount,
            'previousDayClosing' => $previousDayClosing,
            'cashIn' => $cashIn,
            'cashOut' => $cashOut,
            'closingBalance' => $closingBalance,
            'projects' => $projects,
            'parties' => $parties,
            'partySubCategories' => $partySubCategories,
            'landTypes' => $landTypes,
        ]);
    }

    public function reportPdf(Request $request)
    {
        $day = $request->filled('date')
            ? Carbon::parse($request->date)->startOfDay()
            : Carbon::today();

        $dateStr = $day->toDateString();

        $this->syncOpeningFromPreviousDay($day);

        $entries = DayBookEntry::query()
            ->whereDate('entry_date', $day)
            ->orderBy('id')
            ->get();

        $openingRecord = DaybookOpeningBalance::query()
            ->where('balance_date', $dateStr)
            ->firstOrFail();
        $openingAmount = (float) $openingRecord->amount;
        $pettyCashAmount = (float) $openingRecord->petty_cash;

        $prevDay = $day->copy()->subDay();
        $previousDayClosing = $this->computeClosingForDate($prevDay);

        $cashIn = (float) DayBookEntry::query()
            ->whereDate('entry_date', $day)
            ->where('type', DayBookEntry::TYPE_CASH_IN)
            ->sum('amount');
        $cashOut = (float) DayBookEntry::query()
            ->whereDate('entry_date', $day)
            ->where('type', DayBookEntry::TYPE_CASH_OUT)
            ->sum('amount');

        $running = $openingAmount + $pettyCashAmount;
        $tableRows = [];
        foreach ($entries as $e) {
            if ($e->type === DayBookEntry::TYPE_CASH_IN) {
                $running += (float) $e->amount;
                $amountStr = '+Rs '.number_format((float) $e->amount, 0);
                $typeLabel = 'Payment in';
            } else {
                $running -= (float) $e->amount;
                $amountStr = '-Rs '.number_format((float) $e->amount, 0);
                $typeLabel = 'Payment out';
            }
            $tableRows[] = [
                'description' => $e->description ?: '—',
                'type_label' => $typeLabel,
                'amount_str' => $amountStr,
                'balance' => $running,
            ];
        }
        $closingBalance = $running;

        $generatedAt = now();

        $pdf = Pdf::loadView('daybook.report-pdf', [
            'day' => $day,
            'prevDay' => $prevDay,
            'previousDayClosing' => $previousDayClosing,
            'openingAmount' => $openingAmount,
            'pettyCashAmount' => $pettyCashAmount,
            'cashIn' => $cashIn,
            'cashOut' => $cashOut,
            'closingBalance' => $closingBalance,
            'tableRows' => $tableRows,
            'generatedAt' => $generatedAt,
        ]);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'daybook-report-'.$day->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function updatePettyCash(Request $request)
    {
        $validated = $request->validate([
            'balance_date' => ['required', 'date'],
            'petty_cash' => ['required', 'numeric', 'min:0'],
        ]);

        $day = Carbon::parse($validated['balance_date'])->startOfDay();
        $dateStr = $day->toDateString();

        $this->syncOpeningFromPreviousDay($day);

        $record = DaybookOpeningBalance::query()
            ->where('balance_date', $dateStr)
            ->firstOrFail();

        $record->update(['petty_cash' => $validated['petty_cash']]);

        return redirect()
            ->route('daybook.index', ['date' => $dateStr])
            ->with('success', 'Petty cash saved.');
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get();
        $lands = Land::orderBy('name')->get();
        $plots = Plot::with('land')->orderBy('id')->get();
        $factories = Factory::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        return view('daybook.create', compact('projects', 'lands', 'plots', 'factories', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'entry_date' => ['required', 'date'],
                'type' => ['required', 'in:cash_in,cash_out'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'description' => ['nullable', 'string'],
                'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
                'party_id' => ['nullable', 'integer', Rule::exists('parties', 'id')],
                'link_type' => ['nullable', 'in:office,project,land,plot,factory,customer,party'],
                'link_id' => ['nullable', 'integer', 'min:1'],
            ],
            [
                'project_id.exists' => 'The selected project is invalid.',
                'party_id.exists' => 'The selected party is invalid.',
            ]
        );

        if ($request->filled('return_date') && empty($validated['project_id']) && empty($validated['party_id'])) {
            return back()
                ->withErrors(['party_id' => 'Please select a project or a party.'])
                ->withInput();
        }

        $formProjectId = $validated['project_id'] ?? null;
        $formPartyId = $validated['party_id'] ?? null;
        $contextProjectId = null;

        if (! empty($formPartyId)) {
            $validated['link_type'] = 'party';
            $validated['link_id'] = $formPartyId;
            $contextProjectId = $formProjectId ? (int) $formProjectId : null;
        } elseif (! empty($formProjectId)) {
            $validated['link_type'] = 'project';
            $validated['link_id'] = $formProjectId;
            $contextProjectId = (int) $formProjectId;
        } elseif (empty($validated['link_type']) || $validated['link_type'] === 'office') {
            $validated['link_type'] = 'office';
            $validated['link_id'] = null;
        } else {
            if (empty($validated['link_id'])) {
                return back()->withErrors(['link_id' => 'Please select a record to link.'])->withInput();
            }
        }

        unset($validated['project_id'], $validated['party_id']);
        $validated['project_id'] = $contextProjectId;

        DayBookEntry::create($validated);

        $dateParam = $request->input('return_date', $validated['entry_date']);

        return redirect()
            ->route('daybook.index', ['date' => Carbon::parse($dateParam)->toDateString()])
            ->with('success', 'DayBook entry added. It is linked to the selected record.');
    }

    public function show(DayBookEntry $entry)
    {
        return view('daybook.show', compact('entry'));
    }

    public function edit(DayBookEntry $entry)
    {
        $projects = Project::orderBy('name')->get();
        $lands = Land::orderBy('name')->get();
        $plots = Plot::with('land')->orderBy('id')->get();
        $factories = Factory::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        return view('daybook.edit', compact('entry', 'projects', 'lands', 'plots', 'factories', 'customers'));
    }

    public function update(Request $request, DayBookEntry $entry)
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'type' => ['required', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
            'link_type' => ['nullable', 'in:office,project,land,plot,factory,customer,party'],
            'link_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (empty($validated['link_type']) || $validated['link_type'] === 'office') {
            $validated['link_type'] = 'office';
            $validated['link_id'] = null;
        } elseif (empty($validated['link_id'])) {
            return back()->withErrors(['link_id' => 'Please select a record to link.'])->withInput();
        }

        $entry->update($validated);
        return redirect()->route('daybook.index')->with('success', 'Entry updated.');
    }

    public function destroy(DayBookEntry $entry)
    {
        $entry->delete();
        return redirect()->route('daybook.index')->with('success', 'Entry deleted.');
    }
}
