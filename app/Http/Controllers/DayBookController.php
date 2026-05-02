<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DayBookEntry;
use App\Models\DaybookOpeningBalance;
use App\Models\Factory;
use App\Models\Land;
use App\Models\LandType;
use App\Models\Party;
use App\Models\PartySubCategory;
use App\Models\Plot;
use App\Models\Project;
use App\Models\Setting;
use App\Support\LandMeasure;
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

    /**
     * Normalised from/to for ledger (party handled separately).
     * Empty date fields use defaults (month start → today). Request preserves blank inputs for the form.
     *
     * @return array{from: Carbon, to: Carbon, from_input: string, to_input: string}
     */
    private function ledgerDateRangeFromRequest(Request $request): array
    {
        $fromRaw = $request->input('from');
        $toRaw = $request->input('to');

        $request->merge([
            'from' => ($fromRaw !== null && $fromRaw !== '') ? $fromRaw : null,
            'to' => ($toRaw !== null && $toRaw !== '') ? $toRaw : null,
        ]);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $dates = $this->normalizeLedgerDates($validated['from'] ?? null, $validated['to'] ?? null);

        return [
            'from' => $dates['from'],
            'to' => $dates['to'],
            'from_input' => ($fromRaw !== null && $fromRaw !== '') ? (string) $fromRaw : '',
            'to_input' => ($toRaw !== null && $toRaw !== '') ? (string) $toRaw : '',
        ];
    }

    /**
     * @param  mixed  $fromRaw
     * @param  mixed  $toRaw
     * @return array{from: Carbon, to: Carbon}
     */
    private function normalizeLedgerDates($fromRaw, $toRaw): array
    {
        $to = ! empty($toRaw)
            ? Carbon::parse($toRaw)->startOfDay()
            : Carbon::today();
        $from = ! empty($fromRaw)
            ? Carbon::parse($fromRaw)->startOfDay()
            : $to->copy()->startOfMonth();

        if ($from->gt($to)) {
            $tmp = $from->copy();
            $from = $to->copy();
            $to = $tmp;
        }

        if ($from->diffInDays($to) >= 366) {
            $from = $to->copy()->subDays(365);
        }

        return [
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * One calendar day of daybook (opening, petty, entries, closing) for reports / ledger.
     * With $partyId set, only rows linked to that party are included and running balance continues from $partyRunningStart.
     *
     * @return array<string, mixed>|null
     */
    private function buildSingleDayLedger(Carbon $day, ?int $partyId = null, float $partyRunningStart = 0.0): ?array
    {
        $dateStr = $day->toDateString();
        $this->syncOpeningFromPreviousDay($day);

        $openingRecord = DaybookOpeningBalance::query()
            ->where('balance_date', $dateStr)
            ->first();
        if (! $openingRecord) {
            return null;
        }

        $openingAmount = (float) $openingRecord->amount;
        $pettyCashAmount = (float) $openingRecord->petty_cash;

        $prevDay = $day->copy()->subDay();
        $previousDayClosing = $this->computeClosingForDate($prevDay);

        $entriesQuery = DayBookEntry::query()
            ->whereDate('entry_date', $day)
            ->orderBy('id');

        if ($partyId !== null) {
            $entriesQuery
                ->where('link_type', DayBookEntry::LINK_PARTY)
                ->where('link_id', $partyId);
        }

        $entries = $entriesQuery->get();

        if ($partyId !== null) {
            $cashIn = (float) DayBookEntry::query()
                ->whereDate('entry_date', $day)
                ->where('type', DayBookEntry::TYPE_CASH_IN)
                ->where('link_type', DayBookEntry::LINK_PARTY)
                ->where('link_id', $partyId)
                ->sum('amount');
            $cashOut = (float) DayBookEntry::query()
                ->whereDate('entry_date', $day)
                ->where('type', DayBookEntry::TYPE_CASH_OUT)
                ->where('link_type', DayBookEntry::LINK_PARTY)
                ->where('link_id', $partyId)
                ->sum('amount');

            $running = $partyRunningStart;
            $tableRows = [];
            foreach ($entries as $e) {
                $signedDelta = $e->type === DayBookEntry::TYPE_CASH_IN
                    ? (float) $e->amount
                    : -(float) $e->amount;
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
                    'signed_delta' => $signedDelta,
                ];
            }
            $closingBalance = $running;

            return [
                'day' => $day->copy(),
                'prevDay' => $prevDay,
                'previousDayClosing' => $previousDayClosing,
                'openingAmount' => $openingAmount,
                'pettyCashAmount' => $pettyCashAmount,
                'cashIn' => $cashIn,
                'cashOut' => $cashOut,
                'closingBalance' => $closingBalance,
                'tableRows' => $tableRows,
                'party_filter' => true,
                'party_running_open' => $partyRunningStart,
            ];
        }

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
            $signedDelta = $e->type === DayBookEntry::TYPE_CASH_IN
                ? (float) $e->amount
                : -(float) $e->amount;
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
                'signed_delta' => $signedDelta,
            ];
        }
        $closingBalance = $running;

        return [
            'day' => $day->copy(),
            'prevDay' => $prevDay,
            'previousDayClosing' => $previousDayClosing,
            'openingAmount' => $openingAmount,
            'pettyCashAmount' => $pettyCashAmount,
            'cashIn' => $cashIn,
            'cashOut' => $cashOut,
            'closingBalance' => $closingBalance,
            'tableRows' => $tableRows,
            'party_filter' => false,
            'party_running_open' => 0.0,
        ];
    }

    /**
     * Balance column for ledger: negatives as (2,600,000); zero or positive as normal digits.
     */
    private function formatLedgerBalanceCell(float $value): string
    {
        if ($value < 0) {
            return '('.number_format(abs($value), 0).')';
        }

        return number_format($value, 0);
    }

    /**
     * Summary line opening balance: negative as (2,600,000); otherwise "Rs …".
     */
    private function formatLedgerOpeningSummaryLine(float $value): string
    {
        if ($value < 0) {
            return '('.number_format(abs($value), 0).')';
        }

        return 'Rs '.number_format($value, 0);
    }

    /**
     * Strip "Rs" from daybook amount_str for ledger cells; units (Rs.) are shown in column headings.
     */
    private function ledgerStatementAmountCell(string $amountStr): string
    {
        $s = trim($amountStr);
        if ($s === '' || $s === '—') {
            return '—';
        }
        $s = str_replace(['+Rs ', '-Rs ', '+Rs', '-Rs', 'Rs '], ['+', '-', '+', '-', ''], $s);
        $s = ltrim($s);

        return $s === '' ? '—' : $s;
    }

    /**
     * Flat rows for ledger statement: date, payment, amount, description, balance.
     * Running balance is continuous across the range: starts at opening (from date), never reset per day.
     * Amount and balance cells are numeric only; (Rs.) is in the view column titles. Negative balances use parentheses.
     *
     * @return list<array{date: string, payment: string, amount: string, description: string, balance: float, balance_display: string, is_meta?: bool}>
     */
    private function ledgerStatementRows(Carbon $from, Carbon $to, int $partyId): array
    {
        $rows = [];
        $openingBase = $this->ledgerOpeningBalanceForSummary($from);
        $running = $openingBase;

        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $hasPartyLines = DayBookEntry::query()
                ->whereDate('entry_date', $d)
                ->where('link_type', DayBookEntry::LINK_PARTY)
                ->where('link_id', $partyId)
                ->exists();
            if (! $hasPartyLines) {
                continue;
            }

            $block = $this->buildSingleDayLedger($d, $partyId, 0.0);
            if ($block === null) {
                continue;
            }

            $dateLabel = $d->format('d M Y');

            foreach ($block['tableRows'] as $tr) {
                $running += (float) $tr['signed_delta'];
                $rows[] = [
                    'date' => $dateLabel,
                    'payment' => $tr['type_label'],
                    'amount' => $this->ledgerStatementAmountCell($tr['amount_str']),
                    'description' => $tr['description'],
                    'balance' => $running,
                    'balance_display' => $this->formatLedgerBalanceCell($running),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array{0: float, 1: float} [cash in, cash out]
     */
    private function ledgerGrandTotalsForRange(Carbon $from, Carbon $to, int $partyId): array
    {
        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();
        $grandCashIn = (float) DayBookEntry::query()
            ->where('entry_date', '>=', $fromStr)
            ->where('entry_date', '<=', $toStr)
            ->where('type', DayBookEntry::TYPE_CASH_IN)
            ->where('link_type', DayBookEntry::LINK_PARTY)
            ->where('link_id', $partyId)
            ->sum('amount');
        $grandCashOut = (float) DayBookEntry::query()
            ->where('entry_date', '>=', $fromStr)
            ->where('entry_date', '<=', $toStr)
            ->where('type', DayBookEntry::TYPE_CASH_OUT)
            ->where('link_type', DayBookEntry::LINK_PARTY)
            ->where('link_id', $partyId)
            ->sum('amount');

        return [$grandCashIn, $grandCashOut];
    }

    /**
     * Right-aligned totals block under the ledger table (web + PDF).
     *
     * @return list<array{label: string, value: string}>
     */
    private function ledgerTableFooterRows(float $openingBalanceSummary, float $grandCashIn, float $grandCashOut, array $ledgerRows): array
    {
        if ($ledgerRows === []) {
            return [
                ['label' => 'Balance', 'value' => $this->formatLedgerOpeningSummaryLine($openingBalanceSummary)],
            ];
        }

        $closing = (float) end($ledgerRows)['balance'];
        $lines = [];
        if ($openingBalanceSummary != 0.0) {
            $lines[] = ['label' => 'Opening Balance', 'value' => $this->formatLedgerOpeningSummaryLine($openingBalanceSummary)];
        }
        if ($grandCashIn > 0.0) {
            $lines[] = ['label' => 'Total Received', 'value' => 'Rs '.number_format($grandCashIn, 0)];
        }
        if ($grandCashOut > 0.0) {
            $lines[] = ['label' => 'Total Given', 'value' => 'Rs '.number_format($grandCashOut, 0)];
        }
        $lines[] = ['label' => 'Closing Balance', 'value' => $this->formatLedgerOpeningSummaryLine($closing)];

        return $lines;
    }

    /**
     * Carried cash opening on the first day of the ledger range (for summary line, not table rows).
     */
    private function ledgerOpeningBalanceForSummary(Carbon $from): float
    {
        $this->syncOpeningFromPreviousDay($from);
        $rec = DaybookOpeningBalance::query()
            ->where('balance_date', $from->toDateString())
            ->first();

        return $rec ? (float) $rec->amount : 0.0;
    }

    public function index(Request $request)
    {
        $day = $request->filled('date')
            ? Carbon::parse($request->date)->startOfDay()
            : (Setting::daybookDefaultCalendarDay() ?? Carbon::today());

        $dateStr = $day->toDateString();

        $this->syncOpeningFromPreviousDay($day);

        $entries = DayBookEntry::query()
            ->whereDate('entry_date', $day)
            ->with(['partySubCategory.category'])
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
            'daybookProjectsJson' => $this->daybookProjectsJsonPayload(),
            'parties' => $parties,
            'partySubCategories' => $partySubCategories,
            'landTypes' => $landTypes,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function daybookProjectsJsonPayload()
    {
        return Project::query()
            ->orderBy('name')
            ->with('parties')
            ->get()
            ->map(function (Project $p) {
                return array_merge(
                    ['id' => $p->id, 'label' => $p->name],
                    LandMeasure::projectPartyAreaPayload($p)
                );
            })
            ->values();
    }

    public function ledger(Request $request)
    {
        $range = $this->ledgerDateRangeFromRequest($request);
        $from = $range['from'];
        $to = $range['to'];
        $ledger_from_input = $range['from_input'];
        $ledger_to_input = $range['to_input'];
        $parties = Party::query()->orderBy('name')->get();

        $emptyPayload = [
            'from' => $from,
            'to' => $to,
            'ledger_from_input' => $ledger_from_input,
            'ledger_to_input' => $ledger_to_input,
            'party_id' => null,
            'selectedParty' => null,
            'parties' => $parties,
            'ledgerRows' => [],
            'grandCashIn' => 0.0,
            'grandCashOut' => 0.0,
            'openingBalanceSummary' => 0.0,
            'openingBalanceSummaryDisplay' => $this->formatLedgerOpeningSummaryLine(0.0),
            'ledgerFooter' => [],
            'ledger_ready' => false,
        ];

        if ($request->filled('_ledger') && ! $request->filled('party_id')) {
            return view('daybook.ledger', $emptyPayload)
                ->withErrors(['party_id' => 'Please select a party first.']);
        }

        if (! $request->filled('party_id')) {
            return view('daybook.ledger', $emptyPayload);
        }

        $validated = $request->validate([
            'party_id' => ['required', 'integer', Rule::exists('parties', 'id')],
        ]);
        $partyId = (int) $validated['party_id'];

        $ledgerRows = $this->ledgerStatementRows($from, $to, $partyId);
        [$grandCashIn, $grandCashOut] = $this->ledgerGrandTotalsForRange($from, $to, $partyId);
        $openingBalanceSummary = $this->ledgerOpeningBalanceForSummary($from);
        $ledgerFooter = $this->ledgerTableFooterRows($openingBalanceSummary, $grandCashIn, $grandCashOut, $ledgerRows);

        $selectedParty = Party::query()->findOrFail($partyId);

        return view('daybook.ledger', [
            'from' => $from,
            'to' => $to,
            'ledger_from_input' => $ledger_from_input,
            'ledger_to_input' => $ledger_to_input,
            'party_id' => $partyId,
            'selectedParty' => $selectedParty,
            'parties' => $parties,
            'ledgerRows' => $ledgerRows,
            'grandCashIn' => $grandCashIn,
            'grandCashOut' => $grandCashOut,
            'openingBalanceSummary' => $openingBalanceSummary,
            'openingBalanceSummaryDisplay' => $this->formatLedgerOpeningSummaryLine($openingBalanceSummary),
            'ledgerFooter' => $ledgerFooter,
            'ledger_ready' => true,
        ]);
    }

    public function ledgerPdf(Request $request)
    {
        $fromRaw = $request->input('from');
        $toRaw = $request->input('to');
        $request->merge([
            'from' => ($fromRaw !== null && $fromRaw !== '') ? $fromRaw : null,
            'to' => ($toRaw !== null && $toRaw !== '') ? $toRaw : null,
        ]);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'party_id' => ['required', 'integer', Rule::exists('parties', 'id')],
        ]);
        $dates = $this->normalizeLedgerDates($validated['from'] ?? null, $validated['to'] ?? null);
        $from = $dates['from'];
        $to = $dates['to'];
        $partyId = (int) $validated['party_id'];

        $ledgerRows = $this->ledgerStatementRows($from, $to, $partyId);
        [$grandCashIn, $grandCashOut] = $this->ledgerGrandTotalsForRange($from, $to, $partyId);
        $openingBalanceSummary = $this->ledgerOpeningBalanceForSummary($from);
        $ledgerFooter = $this->ledgerTableFooterRows($openingBalanceSummary, $grandCashIn, $grandCashOut, $ledgerRows);

        $generatedAt = now();
        $selectedParty = Party::query()->findOrFail($partyId);

        $pdf = Pdf::loadView('daybook.ledger-pdf', [
            'from' => $from,
            'to' => $to,
            'party_id' => $partyId,
            'selectedParty' => $selectedParty,
            'ledgerRows' => $ledgerRows,
            'grandCashIn' => $grandCashIn,
            'grandCashOut' => $grandCashOut,
            'openingBalanceSummary' => $openingBalanceSummary,
            'openingBalanceSummaryDisplay' => $this->formatLedgerOpeningSummaryLine($openingBalanceSummary),
            'ledgerFooter' => $ledgerFooter,
            'generatedAt' => $generatedAt,
        ]);
        $pdf->setPaper('a4', 'portrait');

        $filename = 'daybook-ledger-'.$from->format('Y-m-d').'_to_'.$to->format('Y-m-d').'-party-'.$partyId.'.pdf';

        return $pdf->download($filename);
    }

    public function reportPdf(Request $request)
    {
        $day = $request->filled('date')
            ? Carbon::parse($request->date)->startOfDay()
            : Carbon::today();

        $data = $this->buildSingleDayLedger($day);
        if ($data === null) {
            abort(404);
        }

        $generatedAt = now();

        $pdf = Pdf::loadView('daybook.report-pdf', array_merge($data, [
            'generatedAt' => $generatedAt,
        ]));
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

    /**
     * Clear bank / reference when settlement method does not use them (after validation).
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizePaymentSettlement(array $validated): array
    {
        $method = $validated['payment_method'] ?? null;
        if ($method === DayBookEntry::PAYMENT_CASH) {
            $validated['payment_bank'] = null;
            $validated['payment_reference'] = null;
        } elseif ($method === DayBookEntry::PAYMENT_ONLINE) {
            $validated['payment_reference'] = null;
        }

        return $validated;
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get();
        $lands = Land::orderBy('name')->get();
        $plots = Plot::with('land')->orderBy('id')->get();
        $factories = Factory::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();

        $daybookDefaultEntryDate = Setting::daybookDefaultCalendarDay()?->toDateString() ?? now()->toDateString();

        return view('daybook.create', compact('projects', 'lands', 'plots', 'factories', 'customers', 'daybookDefaultEntryDate'));
    }

    public function store(Request $request)
    {
        if (! $request->has('payment_method')) {
            $request->merge(['payment_method' => DayBookEntry::PAYMENT_CASH]);
        }

        $validated = $request->validate(
            [
                'entry_date' => ['required', 'date'],
                'type' => ['required', 'in:cash_in,cash_out'],
                'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/', 'numeric', 'min:0.01'],
                'description' => ['nullable', 'string'],
                'payment_method' => ['required', Rule::in([
                    DayBookEntry::PAYMENT_CASH,
                    DayBookEntry::PAYMENT_ONLINE,
                    DayBookEntry::PAYMENT_CHEQUE,
                    DayBookEntry::PAYMENT_PAYORDER,
                ])],
                'payment_bank' => Rule::when(
                    in_array($request->input('payment_method'), [
                        DayBookEntry::PAYMENT_ONLINE,
                        DayBookEntry::PAYMENT_CHEQUE,
                        DayBookEntry::PAYMENT_PAYORDER,
                    ], true),
                    ['required', 'string', 'max:120', Rule::in(array_values(config('pakistan_banks')))],
                    ['nullable']
                ),
                'payment_reference' => Rule::when(
                    in_array($request->input('payment_method'), [
                        DayBookEntry::PAYMENT_CHEQUE,
                        DayBookEntry::PAYMENT_PAYORDER,
                    ], true),
                    ['required', 'string', 'max:100'],
                    ['nullable']
                ),
                'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
                'party_id' => ['nullable', 'integer', Rule::exists('parties', 'id')],
                'party_sub_category_id' => ['nullable', 'integer', Rule::exists('party_sub_categories', 'id')],
                'link_type' => ['nullable', 'in:office,project,land,plot,factory,customer,party'],
                'link_id' => ['nullable', 'integer', 'min:1'],
            ],
            [
                'project_id.exists' => 'The selected project is invalid.',
                'party_id.exists' => 'The selected party is invalid.',
                'party_sub_category_id.exists' => 'The selected sub category is invalid.',
                'payment_bank.in' => 'Please choose a bank from the list.',
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
        if (empty($validated['party_sub_category_id'])) {
            $validated['party_sub_category_id'] = null;
        }

        $validated = $this->normalizePaymentSettlement($validated);

        DayBookEntry::create($validated);

        $dateParam = $request->input('return_date', $validated['entry_date']);

        return redirect()
            ->route('daybook.index', ['date' => Carbon::parse($dateParam)->toDateString()])
            ->with('success', 'DayBook entry added. It is linked to the selected record.');
    }

    public function show(DayBookEntry $entry)
    {
        $entry->load('partySubCategory.category');

        return view('daybook.show', compact('entry'));
    }

    public function edit(DayBookEntry $entry)
    {
        $entry->load('partySubCategory.category');

        $projects = Project::orderBy('name')->get();
        $parties = Party::orderBy('name')->get();
        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();
        $landTypes = LandType::orderBy('name')->get();

        $formPartyId = $entry->link_type === DayBookEntry::LINK_PARTY ? $entry->link_id : null;
        $formProjectId = null;
        if ($entry->link_type === DayBookEntry::LINK_PROJECT) {
            $formProjectId = $entry->link_id;
        } elseif ($entry->project_id) {
            $formProjectId = $entry->project_id;
        }

        return view('daybook.edit', [
            'entry' => $entry,
            'projects' => $projects,
            'daybookProjectsJson' => $this->daybookProjectsJsonPayload(),
            'parties' => $parties,
            'partySubCategories' => $partySubCategories,
            'landTypes' => $landTypes,
            'daybookProjectIdDefault' => $formProjectId !== null ? (string) $formProjectId : '',
            'daybookPartyIdDefault' => $formPartyId !== null ? (string) $formPartyId : '',
            'daybookPartySubCategoryIdDefault' => $entry->party_sub_category_id !== null ? (string) $entry->party_sub_category_id : '',
            'daybookEntryDate' => $entry->entry_date->format('Y-m-d'),
            'daybookTypeDefault' => $entry->type,
            'daybookAmountDefault' => number_format((float) $entry->amount, 2, '.', ''),
            'daybookDescriptionDefault' => $entry->description ?? '',
            'daybookPaymentMethodDefault' => $entry->payment_method ?? DayBookEntry::PAYMENT_CASH,
            'daybookPaymentBankDefault' => $entry->payment_bank ?? '',
            'daybookPaymentReferenceDefault' => $entry->payment_reference ?? '',
        ]);
    }

    public function update(Request $request, DayBookEntry $entry)
    {
        $validated = $request->validate(
            [
                'entry_date' => ['required', 'date'],
                'type' => ['required', 'in:cash_in,cash_out'],
                'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/', 'numeric', 'min:0.01'],
                'description' => ['nullable', 'string'],
                'payment_method' => ['required', Rule::in([
                    DayBookEntry::PAYMENT_CASH,
                    DayBookEntry::PAYMENT_ONLINE,
                    DayBookEntry::PAYMENT_CHEQUE,
                    DayBookEntry::PAYMENT_PAYORDER,
                ])],
                'payment_bank' => Rule::when(
                    in_array($request->input('payment_method'), [
                        DayBookEntry::PAYMENT_ONLINE,
                        DayBookEntry::PAYMENT_CHEQUE,
                        DayBookEntry::PAYMENT_PAYORDER,
                    ], true),
                    ['required', 'string', 'max:120', Rule::in(array_values(config('pakistan_banks')))],
                    ['nullable']
                ),
                'payment_reference' => Rule::when(
                    in_array($request->input('payment_method'), [
                        DayBookEntry::PAYMENT_CHEQUE,
                        DayBookEntry::PAYMENT_PAYORDER,
                    ], true),
                    ['required', 'string', 'max:100'],
                    ['nullable']
                ),
                'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
                'party_id' => ['nullable', 'integer', Rule::exists('parties', 'id')],
                'party_sub_category_id' => ['nullable', 'integer', Rule::exists('party_sub_categories', 'id')],
                'link_type' => ['nullable', 'in:office,project,land,plot,factory,customer,party'],
                'link_id' => ['nullable', 'integer', 'min:1'],
            ],
            [
                'project_id.exists' => 'The selected project is invalid.',
                'party_id.exists' => 'The selected party is invalid.',
                'party_sub_category_id.exists' => 'The selected sub category is invalid.',
                'payment_bank.in' => 'Please choose a bank from the list.',
            ]
        );

        if (empty($validated['project_id']) && empty($validated['party_id'])) {
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
        if (empty($validated['party_sub_category_id'])) {
            $validated['party_sub_category_id'] = null;
        }

        $validated = $this->normalizePaymentSettlement($validated);

        $entry->update($validated);

        return redirect()
            ->route('daybook.show', $entry)
            ->with('success', 'DayBook entry updated.');
    }

    public function destroy(DayBookEntry $entry)
    {
        $entry->delete();

        return redirect()->route('daybook.index')->with('success', 'Entry deleted.');
    }
}
