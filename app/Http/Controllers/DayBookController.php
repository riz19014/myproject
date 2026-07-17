<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DayBookEntry;
use App\Models\DaybookOpeningBalance;
use App\Models\Factory;
use App\Models\FileSaleLand;
use App\Models\Land;
use App\Models\LandType;
use App\Models\Party;
use App\Models\PartyCategory;
use App\Models\PartySubCategory;
use App\Models\Plot;
use App\Models\Project;
use App\Models\PurchaseFile;
use App\Models\Setting;
use App\Support\DaybookVoucher;
use App\Support\LandMeasure;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DayBookController extends Controller
{
    /**
     * Party sub-categories limited to "Construction & Material" (Factory expenses UI).
     *
     * @return \Illuminate\Support\Collection<int, array{id:int,label:string,unit:?string}>
     */
    private function factoryConstructionSubCategoriesJsonPayload(): Collection
    {
        return PartySubCategory::query()
            ->select(['id', 'name', 'unit', 'category_id'])
            ->whereHas('category', fn ($q) => $q->where('name', 'Construction & Material'))
            ->orderBy('name')
            ->get()
            ->map(fn (PartySubCategory $sc) => [
                'id' => $sc->id,
                'label' => $sc->name,
                'unit' => $sc->unit,
            ])
            ->values();
    }

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
            ->with('paidByParty')
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
                    'settlement' => $e->getSettlementWithPaidByLabel(),
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
                'settlement' => $e->getSettlementWithPaidByLabel(),
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
     * Flat rows for party ledger: date, payment, settlement, amount, description, balance.
     * Running balance starts at opening (from date) and follows party-linked entries in date order.
     *
     * @return list<array{date: string, payment: string, settlement: string, amount: string, description: string, balance: float, balance_display: string, is_meta?: bool}>
     */
    private function ledgerStatementRows(Carbon $from, Carbon $to, int $partyId): array
    {
        $openingBase = $this->ledgerOpeningBalanceForSummary($from);
        $running = $openingBase;
        $rows = [];

        $entries = DayBookEntry::query()
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->where('link_type', DayBookEntry::LINK_PARTY)
            ->where('link_id', $partyId)
            ->with('paidByParty')
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

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

            $rows[] = [
                'date' => $e->entry_date->format('d M Y'),
                'payment' => $typeLabel,
                'settlement' => $e->getSettlementWithPaidByLabel(),
                'amount' => $this->ledgerStatementAmountCell($amountStr),
                'description' => $e->description ?: '—',
                'balance' => $running,
                'balance_display' => $this->formatLedgerBalanceCell($running),
            ];
        }

        return $rows;
    }

    /**
     * All daybook entries in range (every link type), chronological, with running balance from opening + petty on from-date.
     *
     * @return list<array{date: string, payment: string, settlement: string, amount: string, description: string, balance: float, balance_display: string, is_meta?: bool}>
     */
    private function ledgerStatementRowsDateWise(Carbon $from, Carbon $to): array
    {
        $this->syncOpeningFromPreviousDay($from);
        $opening = $this->ledgerOpeningBalanceForSummary($from);
        $petty = $this->ledgerPettyCashForSummary($from);
        $running = $opening + $petty;

        $entries = DayBookEntry::query()
            ->whereBetween('entry_date', [$from->toDateString(), $to->toDateString()])
            ->with('paidByParty')
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $rows = [];
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
            $linkLabel = $e->getLinkLabel();
            $desc = ($e->description ?: '—');
            if ($linkLabel !== '' && $linkLabel !== 'Office') {
                $desc .= ' · '.$linkLabel;
            }

            $rows[] = [
                'date' => $e->entry_date->format('d M Y'),
                'payment' => $typeLabel,
                'settlement' => $e->getSettlementWithPaidByLabel(),
                'amount' => $this->ledgerStatementAmountCell($amountStr),
                'description' => $desc,
                'balance' => $running,
                'balance_display' => $this->formatLedgerBalanceCell($running),
            ];
        }

        return $rows;
    }

    private function ledgerPettyCashForSummary(Carbon $from): float
    {
        $this->syncOpeningFromPreviousDay($from);
        $rec = DaybookOpeningBalance::query()
            ->where('balance_date', $from->toDateString())
            ->first();

        return $rec ? (float) $rec->petty_cash : 0.0;
    }

    private function earliestPartyEntryDate(int $partyId): ?Carbon
    {
        $min = DayBookEntry::query()
            ->where('link_type', DayBookEntry::LINK_PARTY)
            ->where('link_id', $partyId)
            ->min('entry_date');
        if ($min === null) {
            return null;
        }

        return Carbon::parse($min)->startOfDay();
    }

    /**
     * @return array{0: float, 1: float} [cash in, cash out] for all links in range
     */
    private function ledgerGrandTotalsAllForRange(Carbon $from, Carbon $to): array
    {
        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();
        $grandCashIn = (float) DayBookEntry::query()
            ->where('entry_date', '>=', $fromStr)
            ->where('entry_date', '<=', $toStr)
            ->where('type', DayBookEntry::TYPE_CASH_IN)
            ->sum('amount');
        $grandCashOut = (float) DayBookEntry::query()
            ->where('entry_date', '>=', $fromStr)
            ->where('entry_date', '<=', $toStr)
            ->where('type', DayBookEntry::TYPE_CASH_OUT)
            ->sum('amount');

        return [$grandCashIn, $grandCashOut];
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

    /**
     * @param  \Illuminate\Support\Collection<int, DayBookEntry>  $entries
     * @return array{
     *     cash: array{in: float, out: float, net: float},
     *     bank: array{
     *         in: float,
     *         out: float,
     *         net: float,
     *         payorder: array{in: float, out: float, net: float},
     *         cheque: array{in: float, out: float, net: float},
     *         online: array{in: float, out: float, net: float},
     *     }
     * }
     */
    private function daybookPaymentBreakdown(Collection $entries): array
    {
        $methods = [
            DayBookEntry::PAYMENT_CASH => ['in' => 0.0, 'out' => 0.0],
            DayBookEntry::PAYMENT_PAYORDER => ['in' => 0.0, 'out' => 0.0],
            DayBookEntry::PAYMENT_CHEQUE => ['in' => 0.0, 'out' => 0.0],
            DayBookEntry::PAYMENT_ONLINE => ['in' => 0.0, 'out' => 0.0],
        ];

        foreach ($entries as $entry) {
            $method = $entry->payment_method;
            if ($method === null || $method === '') {
                $method = DayBookEntry::PAYMENT_CASH;
            }
            if (! isset($methods[$method])) {
                continue;
            }

            $amount = (float) $entry->amount;
            if ($entry->type === DayBookEntry::TYPE_CASH_IN) {
                $methods[$method]['in'] += $amount;
            } else {
                $methods[$method]['out'] += $amount;
            }
        }

        $withNet = static function (array $bucket): array {
            return [
                'in' => $bucket['in'],
                'out' => $bucket['out'],
                'net' => $bucket['in'] - $bucket['out'],
                'total' => $bucket['in'] + $bucket['out'],
            ];
        };

        $cash = $withNet($methods[DayBookEntry::PAYMENT_CASH]);
        $payorder = $withNet($methods[DayBookEntry::PAYMENT_PAYORDER]);
        $cheque = $withNet($methods[DayBookEntry::PAYMENT_CHEQUE]);
        $online = $withNet($methods[DayBookEntry::PAYMENT_ONLINE]);

        $bankIn = $payorder['in'] + $cheque['in'] + $online['in'];
        $bankOut = $payorder['out'] + $cheque['out'] + $online['out'];

        return [
            'cash' => $cash,
            'bank' => [
                'in' => $bankIn,
                'out' => $bankOut,
                'net' => $bankIn - $bankOut,
                'total' => $bankIn + $bankOut,
                'payorder' => $payorder,
                'cheque' => $cheque,
                'online' => $online,
            ],
        ];
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
            ->with(['partySubCategory.category', 'purchaseFile', 'paidByParty'])
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

        $paymentBreakdown = $this->daybookPaymentBreakdown($entries);

        $projects = Project::orderBy('name')->get();
        $parties = Party::orderBy('name')->get();
        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        $landTypes = LandType::orderBy('name')->get();
        $partyCategories = PartyCategory::orderBy('name')->get();

        $daybookProjectIdDefault = '';
        $daybookPurchaseFileIdDefault = '';
        if ($request->filled('project') && Project::query()->whereKey((int) $request->query('project'))->exists()) {
            $daybookProjectIdDefault = (string) (int) $request->query('project');
            if ($request->filled('purchase_file_id')) {
                $fileOk = PurchaseFile::query()
                    ->whereKey((int) $request->query('purchase_file_id'))
                    ->where('project_id', (int) $daybookProjectIdDefault)
                    ->whereHas('fileSaleLand')
                    ->exists();
                if ($fileOk) {
                    $daybookPurchaseFileIdDefault = (string) (int) $request->query('purchase_file_id');
                }
            }
        }

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
            'paymentBreakdown' => $paymentBreakdown,
            'projects' => $projects,
            'daybookProjectsJson' => $this->daybookProjectsJsonPayload(),
            'factoryConstructionSubCategoriesJson' => $this->factoryConstructionSubCategoriesJsonPayload(),
            'parties' => $parties,
            'partySubCategories' => $partySubCategories,
            'partyCategories' => $partyCategories,
            'landTypes' => $landTypes,
            'daybookProjectIdDefault' => $daybookProjectIdDefault,
            'daybookPurchaseFileIdDefault' => $daybookPurchaseFileIdDefault,
        ]);
    }

    /**
     * Global searchable list of recent daybook lines (separate from the daily daybook screen).
     */
    public function entries(Request $request)
    {
        $highlightDate = null;
        if ($request->filled('date')) {
            try {
                $highlightDate = Carbon::parse($request->query('date'))->toDateString();
            } catch (\Throwable) {
                $highlightDate = null;
            }
        }

        $sidebarEntryRows = $this->buildDaybookSidebarRows(1200);

        return view('daybook.entries', [
            'sidebarEntryRows' => $sidebarEntryRows,
            'highlightDate' => $highlightDate,
        ]);
    }

    /**
     * Recent daybook lines for the global entries screen: precomputed labels and a flat search string.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function buildDaybookSidebarRows(int $limit): Collection
    {
        $entries = DayBookEntry::query()
            ->with(['project.landType', 'partySubCategory.category', 'subCategory.category', 'purchaseFile', 'paidByParty'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        if ($entries->isEmpty()) {
            return collect();
        }

        $partyIds = $entries->where('link_type', DayBookEntry::LINK_PARTY)->pluck('link_id')->filter()->unique()->all();
        $projectLinkIds = $entries->where('link_type', DayBookEntry::LINK_PROJECT)->pluck('link_id')->filter()->unique()->all();
        $landIds = $entries->where('link_type', DayBookEntry::LINK_LAND)->pluck('link_id')->filter()->unique()->all();
        $plotIds = $entries->where('link_type', DayBookEntry::LINK_PLOT)->pluck('link_id')->filter()->unique()->all();
        $factoryIds = $entries->where('link_type', DayBookEntry::LINK_FACTORY)->pluck('link_id')->filter()->unique()->all();
        $customerIds = $entries->where('link_type', DayBookEntry::LINK_CUSTOMER)->pluck('link_id')->filter()->unique()->all();

        $parties = Party::query()->whereIn('id', $partyIds)->get()->keyBy('id');
        $projectsByLink = Project::query()->whereIn('id', $projectLinkIds)->with('landType')->get()->keyBy('id');
        $lands = Land::query()->whereIn('id', $landIds)->get()->keyBy('id');
        $plots = Plot::query()->whereIn('id', $plotIds)->with('land')->get()->keyBy('id');
        $factories = Factory::query()->whereIn('id', $factoryIds)->get()->keyBy('id');
        $customers = Customer::query()->whereIn('id', $customerIds)->get()->keyBy('id');

        return $entries->map(function (DayBookEntry $e) use ($parties, $projectsByLink, $lands, $plots, $factories, $customers) {
            $linkLabel = $this->daybookSidebarLinkLabel($e, $parties, $projectsByLink, $lands, $plots, $factories, $customers);
            $linkProject = ($e->link_type === DayBookEntry::LINK_PROJECT && $e->link_id)
                ? $projectsByLink->get((int) $e->link_id)
                : null;
            $linkedProjectAreaLine = $linkProject ? $this->formatDaybookProjectLandLine($linkProject) : '';
            $subCat = $e->getPartySubCategoryLabel();
            $categoryName = $e->partySubCategory?->category?->name ?? '';
            $settlement = $e->getSettlementLabel();
            $ctxProject = $e->project;
            $projectName = $ctxProject?->name ?? '';
            $projectArea = $ctxProject ? $this->formatDaybookProjectLandLine($ctxProject) : '';
            $landTypeName = $ctxProject?->landType?->name ?? '';
            $purchaseFileName = $e->purchaseFile?->file_name ?? '';
            $typeLabel = $e->type === DayBookEntry::TYPE_CASH_IN ? 'Payment in' : 'Payment out';
            $dateStr = $e->entry_date->format('Y-m-d');
            $dateDisp = $e->entry_date->format('j M Y');
            $dateDispShort = $e->entry_date->format('d-M-y');
            $voucherNo = $e->voucher_no;
            $voucherDisplay = $e->getVoucherNumber();

            $parts = array_filter([
                (string) $e->id,
                $dateStr,
                $dateDisp,
                $dateDispShort,
                $voucherNo !== null ? (string) $voucherNo : '',
                $voucherNo !== null ? strtolower($voucherDisplay) : '',
                $voucherNo !== null ? 'voucher '.$voucherNo : '',
                strtolower($e->description ?? ''),
                $e->type,
                $typeLabel,
                (string) $e->amount,
                number_format((float) $e->amount, 0),
                number_format((float) $e->amount, 2),
                strtolower($e->payment_method ?? ''),
                strtolower($e->payment_bank ?? ''),
                strtolower($e->payment_reference ?? ''),
                strtolower($e->paidByParty?->name ?? ''),
                strtolower($settlement),
                strtolower($linkLabel),
                strtolower($linkProject?->name ?? ''),
                strtolower($linkedProjectAreaLine),
                strtolower($e->link_type ?? ''),
                $e->link_id !== null ? (string) $e->link_id : '',
                strtolower($projectName),
                strtolower($projectArea),
                strtolower($landTypeName),
                strtolower($purchaseFileName),
                strtolower($e->getSoldAreaLabel()),
                strtolower($subCat),
                strtolower($categoryName),
            ]);

            $searchBlob = mb_strtolower(implode(' ', $parts), 'UTF-8');

            return [
                'id' => $e->id,
                'url' => route('daybook.show', $e),
                'entry_date' => $dateStr,
                'date_display' => $dateDisp,
                'voucher_no' => $voucherNo,
                'voucher_display' => $voucherNo !== null ? $voucherDisplay : '',
                'description' => $e->description,
                'type_label' => $typeLabel,
                'amount' => (float) $e->amount,
                'is_cash_in' => $e->type === DayBookEntry::TYPE_CASH_IN,
                'amount_display' => number_format((float) $e->amount, 0),
                'settlement' => $settlement,
                'paid_by' => $e->getPaidByLabel(),
                'link_label' => $linkLabel,
                'linked_project_name' => $linkProject?->name ?? '',
                'linked_project_area' => $linkedProjectAreaLine,
                'project_name' => $projectName,
                'project_area' => $projectArea,
                'sub_category' => $subCat,
                'category' => $categoryName,
                'land_type' => $landTypeName,
                'purchase_file_name' => $purchaseFileName,
                'sold_area' => $e->getSoldAreaLabel(),
                'is_factory' => $e->isFactoryExpense(),
                'factory_sub_category' => $e->getFactorySubCategoryLabel(),
                'unit' => $e->unit ?? '',
                'quantity' => $e->quantity !== null ? number_format((int) $e->quantity) : '',
                'unit_price' => $e->unit_price !== null ? number_format((float) $e->unit_price, 2) : '',
                'is_today' => $e->entry_date->toDateString() === Carbon::today()->toDateString(),
                'search_blob' => $searchBlob,
            ];
        });
    }

    private function daybookSidebarLinkLabel(
        DayBookEntry $e,
        Collection $parties,
        Collection $projectsByLink,
        Collection $lands,
        Collection $plots,
        Collection $factories,
        Collection $customers
    ): string {
        if ($e->link_type === DayBookEntry::LINK_OFFICE || ! $e->link_type) {
            return 'Office';
        }
        if ($e->link_type === DayBookEntry::LINK_PARTY && $e->link_id) {
            $p = $parties->get((int) $e->link_id);

            return 'Party: '.($p?->name ?? ('#'.$e->link_id));
        }
        if ($e->link_type === DayBookEntry::LINK_PROJECT && $e->link_id) {
            $p = $projectsByLink->get((int) $e->link_id);

            return $p?->name ?? ('Project #'.$e->link_id);
        }
        if ($e->link_type === DayBookEntry::LINK_LAND && $e->link_id) {
            $l = $lands->get((int) $e->link_id);

            return $l?->name ?? ('Land #'.$e->link_id);
        }
        if ($e->link_type === DayBookEntry::LINK_PLOT && $e->link_id) {
            $plot = $plots->get((int) $e->link_id);
            if (! $plot) {
                return 'Plot #'.$e->link_id;
            }
            $landName = $plot->relationLoaded('land') && $plot->land ? $plot->land->name : '—';

            return 'Plot: '.$plot->plot_number.' ('.$landName.')';
        }
        if ($e->link_type === DayBookEntry::LINK_FACTORY && $e->link_id) {
            $f = $factories->get((int) $e->link_id);

            return $f?->name ?? ('Factory #'.$e->link_id);
        }
        if ($e->link_type === DayBookEntry::LINK_CUSTOMER && $e->link_id) {
            $c = $customers->get((int) $e->link_id);

            return $c?->name ?? ('Customer #'.$e->link_id);
        }

        return '—';
    }

    private function formatDaybookProjectLandLine(Project $project): string
    {
        $project->loadMissing('parties');
        $marla = LandMeasure::partiesTotalMarla($project->parties);
        if ($marla <= 0) {
            return '';
        }

        return LandMeasure::formatAkmsLabelFromMarla($marla).' · '.LandMeasure::formatMarlaTotal($marla);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function daybookProjectsJsonPayload(?int $excludeDaybookEntryId = null)
    {
        $movedFileIdsByProject = FileSaleLand::query()
            ->get(['project_id', 'sale_land_id'])
            ->groupBy('project_id')
            ->map(fn (Collection $rows) => $rows->pluck('sale_land_id')->map(fn ($id) => (int) $id)->all());

        return Project::query()
            ->orderBy('name')
            ->with([
                'landType',
                'parties',
                'purchaseFiles' => fn ($q) => $q->orderBy('file_name')->with(['purchaseItems.party', 'fileSaleLand', 'sales']),
            ])
            ->get()
            ->map(function (Project $p) use ($movedFileIdsByProject, $excludeDaybookEntryId) {
                $landTypeName = $p->landType?->name;
                $isFactory = $landTypeName !== null && mb_strtolower(trim($landTypeName)) === 'factory';
                $movedIds = $movedFileIdsByProject->get($p->id, []);

                $saleFiles = $p->purchaseFiles
                    ->filter(fn (PurchaseFile $f) => in_array((int) $f->id, $movedIds, true))
                    ->values()
                    ->map(fn (PurchaseFile $f) => $f->daybookSaleFilePayload($excludeDaybookEntryId))
                    ->all();

                return array_merge(
                    [
                        'id' => $p->id,
                        'label' => $p->name,
                        'land_type' => $landTypeName,
                        'is_factory' => $isFactory,
                        'sale_files' => $saleFiles,
                        'purchase_files' => collect($saleFiles)->map(fn (array $f) => [
                            'id' => $f['id'],
                            'label' => $f['label'],
                        ])->values()->all(),
                    ],
                    LandMeasure::projectPartyAreaPayload($p)
                );
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function applyPurchaseFileToEntry(array $validated, ?int $contextProjectId, ?int $excludeEntryId = null): array
    {
        $fileId = $validated['purchase_file_id'] ?? null;
        unset($validated['purchase_file_id']);

        $soldAreaQty = $validated['sold_area_qty'] ?? null;
        $soldAreaUnit = $validated['sold_area_unit'] ?? null;
        unset($validated['sold_area_qty'], $validated['sold_area_unit'], $validated['sold_area_marla']);

        $hasSoldAreaInput = $soldAreaQty !== null && $soldAreaQty !== '' && (float) $soldAreaQty > 0;

        if (empty($fileId)) {
            if ($hasSoldAreaInput) {
                throw ValidationException::withMessages([
                    'purchase_file_id' => 'Select a project sale file before entering sold area.',
                ]);
            }
            $validated['purchase_file_id'] = null;
            $validated['sold_area_marla'] = null;
            $validated['sold_area_qty'] = null;
            $validated['sold_area_unit'] = null;

            return $validated;
        }

        if (empty($contextProjectId)) {
            throw ValidationException::withMessages([
                'purchase_file_id' => 'Select a project before choosing a sale file.',
            ]);
        }

        /** @var PurchaseFile|null $file */
        $file = PurchaseFile::query()
            ->where('id', (int) $fileId)
            ->where('project_id', (int) $contextProjectId)
            ->with(['purchaseItems', 'fileSaleLand', 'sales'])
            ->lockForUpdate()
            ->first();

        if (! $file) {
            throw ValidationException::withMessages([
                'purchase_file_id' => 'The selected file does not belong to this project.',
            ]);
        }

        if (! $file->isMovedToFileSale()) {
            throw ValidationException::withMessages([
                'purchase_file_id' => 'Only files moved to File Sale can be selected.',
            ]);
        }

        $validated['purchase_file_id'] = (int) $file->id;

        if (! $hasSoldAreaInput) {
            $validated['sold_area_marla'] = null;
            $validated['sold_area_qty'] = null;
            $validated['sold_area_unit'] = null;

            return $validated;
        }

        $unit = is_string($soldAreaUnit) ? strtolower(trim($soldAreaUnit)) : '';
        if (! in_array($unit, ['marla', 'kanal', 'acre', 'sqft'], true)) {
            throw ValidationException::withMessages([
                'sold_area_unit' => 'Choose a valid area unit (marla, kanal, acre, or sq ft).',
            ]);
        }

        $qty = round((float) $soldAreaQty, 4);
        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'sold_area_qty' => 'Sold area must be greater than zero.',
            ]);
        }

        $soldMarla = round(LandMeasure::toMarla($qty, $unit), 6);
        if ($soldMarla <= 1e-6) {
            throw ValidationException::withMessages([
                'sold_area_qty' => 'Sold area must be greater than zero.',
            ]);
        }

        $remaining = $file->remainingLandMarla($excludeEntryId);
        if ($soldMarla - $remaining > 1e-4) {
            throw ValidationException::withMessages([
                'sold_area_qty' => 'Cannot sell more than the available balance ('
                    .LandMeasure::formatAkmsLabelFromMarla($remaining).').',
            ]);
        }

        if ($file->isFullySold($excludeEntryId) && $excludeEntryId === null) {
            throw ValidationException::withMessages([
                'purchase_file_id' => 'This sale file is fully sold and cannot accept further sales.',
            ]);
        }

        $validated['sold_area_marla'] = number_format($soldMarla, 6, '.', '');
        $validated['sold_area_qty'] = number_format($qty, 4, '.', '');
        $validated['sold_area_unit'] = $unit;

        return $validated;
    }

    public function ledger(Request $request)
    {
        $parties = Party::query()->orderBy('name')->get();

        $fromRaw = $request->input('from');
        $toRaw = $request->input('to');
        $hasFromInput = $fromRaw !== null && $fromRaw !== '';
        $hasToInput = $toRaw !== null && $toRaw !== '';

        $defaultRange = $this->normalizeLedgerDates(null, null);
        $emptyPayload = [
            'from' => $defaultRange['from'],
            'to' => $defaultRange['to'],
            'ledger_from_input' => '',
            'ledger_to_input' => '',
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
            'ledger_scope' => null,
        ];

        if (! $request->filled('_ledger')) {
            return view('daybook.ledger', $emptyPayload);
        }

        $partyId = null;
        if ($request->filled('party_id')) {
            $validatedParty = $request->validate([
                'party_id' => ['required', 'integer', Rule::exists('parties', 'id')],
            ], [], ['party_id' => 'party']);
            $partyId = (int) $validatedParty['party_id'];
        }

        $ledger_scope = 'date_range';
        $from = null;
        $to = null;
        $ledger_from_input = '';
        $ledger_to_input = '';

        if ($partyId !== null && ! $hasFromInput && ! $hasToInput) {
            $ledger_scope = 'party_all_time';
            $earliest = $this->earliestPartyEntryDate($partyId);
            $from = $earliest ?? Carbon::today();
            $to = Carbon::today();
            if ($from->gt($to)) {
                $from = $to->copy();
            }
            $maxDays = 366 * 40;
            if ($from->diffInDays($to) > $maxDays) {
                $from = $to->copy()->subDays($maxDays);
            }
        } else {
            $range = $this->ledgerDateRangeFromRequest($request);
            $from = $range['from'];
            $to = $range['to'];
            $ledger_from_input = $range['from_input'];
            $ledger_to_input = $range['to_input'];
            if ($partyId !== null) {
                $ledger_scope = 'party_date_range';
            }
        }

        if ($partyId === null) {
            $ledgerRows = $this->ledgerStatementRowsDateWise($from, $to);
            [$grandCashIn, $grandCashOut] = $this->ledgerGrandTotalsAllForRange($from, $to);
        } else {
            $ledgerRows = $this->ledgerStatementRows($from, $to, $partyId);
            [$grandCashIn, $grandCashOut] = $this->ledgerGrandTotalsForRange($from, $to, $partyId);
        }

        $openingBalanceSummary = $this->ledgerOpeningBalanceForSummary($from);
        $ledgerFooter = $this->ledgerTableFooterRows($openingBalanceSummary, $grandCashIn, $grandCashOut, $ledgerRows);

        $selectedParty = $partyId !== null ? Party::query()->findOrFail($partyId) : null;

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
            'ledger_scope' => $ledger_scope,
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
            'party_id' => ['nullable', 'integer', Rule::exists('parties', 'id')],
        ]);

        $hasFromInput = $fromRaw !== null && $fromRaw !== '';
        $hasToInput = $toRaw !== null && $toRaw !== '';
        $partyId = isset($validated['party_id']) && $validated['party_id'] !== null && $validated['party_id'] !== ''
            ? (int) $validated['party_id']
            : null;

        if ($partyId !== null && ! $hasFromInput && ! $hasToInput) {
            $earliest = $this->earliestPartyEntryDate($partyId);
            $from = $earliest ?? Carbon::today();
            $to = Carbon::today();
            if ($from->gt($to)) {
                $from = $to->copy();
            }
            $maxDays = 366 * 40;
            if ($from->diffInDays($to) > $maxDays) {
                $from = $to->copy()->subDays($maxDays);
            }
        } else {
            $dates = $this->normalizeLedgerDates($validated['from'] ?? null, $validated['to'] ?? null);
            $from = $dates['from'];
            $to = $dates['to'];
        }

        if ($partyId === null) {
            $ledgerRows = $this->ledgerStatementRowsDateWise($from, $to);
            [$grandCashIn, $grandCashOut] = $this->ledgerGrandTotalsAllForRange($from, $to);
        } else {
            $ledgerRows = $this->ledgerStatementRows($from, $to, $partyId);
            [$grandCashIn, $grandCashOut] = $this->ledgerGrandTotalsForRange($from, $to, $partyId);
        }

        $openingBalanceSummary = $this->ledgerOpeningBalanceForSummary($from);
        $ledgerFooter = $this->ledgerTableFooterRows($openingBalanceSummary, $grandCashIn, $grandCashOut, $ledgerRows);

        $generatedAt = now();
        $selectedParty = $partyId !== null ? Party::query()->findOrFail($partyId) : null;

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

        $slug = $from->format('Y-m-d').'_to_'.$to->format('Y-m-d');
        $filename = $partyId !== null
            ? 'daybook-ledger-'.$slug.'-party-'.$partyId.'.pdf'
            : 'daybook-ledger-'.$slug.'-all-parties.pdf';

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

        $paidByPartyId = $validated['paid_by_party_id'] ?? null;
        $validated['paid_by_party_id'] = ! empty($paidByPartyId) ? (int) $paidByPartyId : null;

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
                'paid_by_party_id' => ['nullable', 'integer', Rule::exists('parties', 'id')],
                'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
                'purchase_file_id' => ['nullable', 'integer', Rule::exists('purchase_files', 'id')],
                'sold_area_qty' => ['nullable', 'numeric', 'min:0'],
                'sold_area_unit' => ['nullable', 'string', Rule::in(['marla', 'kanal', 'acre', 'sqft'])],
                'party_id' => ['nullable', 'integer', Rule::exists('parties', 'id')],
                'party_sub_category_id' => ['nullable', 'integer', Rule::exists('party_sub_categories', 'id')],
                // Factory-only (Construction & Material)
                'sub_category_id' => ['nullable', 'integer', Rule::exists('party_sub_categories', 'id')],
                'unit' => ['nullable', 'string', 'max:50'],
                'quantity' => ['nullable', 'integer', 'min:1'],
                'unit_price' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/', 'numeric', 'min:0.01'],
                'link_type' => ['nullable', 'in:office,project,land,plot,factory,customer,party'],
                'link_id' => ['nullable', 'integer', 'min:1'],
            ],
            [
                'project_id.exists' => 'The selected project is invalid.',
                'purchase_file_id.exists' => 'The selected sale file is invalid.',
                'party_id.exists' => 'The selected party is invalid.',
                'paid_by_party_id.exists' => 'The selected paid-by party is invalid.',
                'party_sub_category_id.exists' => 'The selected sub category is invalid.',
                'sub_category_id.exists' => 'The selected sub category is invalid.',
                'payment_bank.in' => 'Please choose a bank from the list.',
                'sold_area_unit.in' => 'Choose a valid area unit.',
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

        $isFactoryExpense = false;
        if (! empty($contextProjectId)) {
            $project = Project::query()->with('landType')->find($contextProjectId);
            $lt = $project?->landType?->name;
            $isFactoryExpense = $lt !== null && mb_strtolower(trim($lt)) === 'factory';
        }

        if ($isFactoryExpense) {
            $request->validate([
                'sub_category_id' => ['required', 'integer', Rule::exists('party_sub_categories', 'id')],
                'quantity' => ['required', 'integer', 'min:1'],
                'unit_price' => ['required', 'regex:/^\d+(\.\d{1,2})?$/', 'numeric', 'min:0.01'],
            ], [], [
                'sub_category_id' => 'sub category',
                'unit_price' => 'unit price',
            ]);

            $sc = PartySubCategory::query()
                ->where('id', (int) $validated['sub_category_id'])
                ->whereHas('category', fn ($q) => $q->where('name', 'Construction & Material'))
                ->first();
            if (! $sc) {
                return back()
                    ->withErrors(['sub_category_id' => 'Please choose a sub category from Construction & Material.'])
                    ->withInput();
            }

            $validated['unit'] = $sc->unit;
            $validated['quantity'] = (int) $validated['quantity'];
            $validated['unit_price'] = number_format((float) $validated['unit_price'], 2, '.', '');
            $expected = round(((int) $validated['quantity']) * ((float) $validated['unit_price']), 2);
            $amount = round((float) $validated['amount'], 2);
            if (abs($expected - $amount) > 0.009) {
                return back()
                    ->withErrors(['amount' => 'Amount must equal Quantity × Unit Price.'])
                    ->withInput();
            }
        } else {
            $validated['sub_category_id'] = null;
            $validated['unit'] = null;
            $validated['quantity'] = null;
            $validated['unit_price'] = null;
        }

        $entry = DB::transaction(function () use ($validated, $contextProjectId) {
            $validated = $this->applyPurchaseFileToEntry($validated, $contextProjectId);
            $validated = $this->normalizePaymentSettlement($validated);

            $entry = DayBookEntry::create($validated);
            DaybookVoucher::assignIfMissing($entry);

            return $entry;
        });

        $dateParam = $request->input('return_date', $validated['entry_date']);

        return redirect()
            ->route('daybook.index', ['date' => Carbon::parse($dateParam)->toDateString()])
            ->with('success', 'DayBook entry added. It is linked to the selected record.');
    }

    public function show(DayBookEntry $entry)
    {
        $entry->load(['partySubCategory.category', 'subCategory.category', 'purchaseFile', 'project.landType', 'paidByParty']);
        $entry = DaybookVoucher::assignIfMissing($entry);

        return view('daybook.show', compact('entry'));
    }

    public function voucher(DayBookEntry $entry)
    {
        $entry->load(['partySubCategory.category', 'purchaseFile', 'paidByParty']);
        $entry = DaybookVoucher::assignIfMissing($entry);

        return view('daybook.voucher', compact('entry'));
    }

    public function edit(DayBookEntry $entry)
    {
        $entry->load(['partySubCategory.category', 'purchaseFile', 'paidByParty']);

        $projects = Project::orderBy('name')->get();
        $parties = Party::orderBy('name')->get();
        $partySubCategories = PartySubCategory::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();
        $landTypes = LandType::orderBy('name')->get();
        $partyCategories = PartyCategory::orderBy('name')->get();

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
            'daybookProjectsJson' => $this->daybookProjectsJsonPayload((int) $entry->id),
            'factoryConstructionSubCategoriesJson' => $this->factoryConstructionSubCategoriesJsonPayload(),
            'parties' => $parties,
            'partySubCategories' => $partySubCategories,
            'partyCategories' => $partyCategories,
            'landTypes' => $landTypes,
            'daybookProjectIdDefault' => $formProjectId !== null ? (string) $formProjectId : '',
            'daybookPartyIdDefault' => $formPartyId !== null ? (string) $formPartyId : '',
            'daybookPartySubCategoryIdDefault' => $entry->party_sub_category_id !== null ? (string) $entry->party_sub_category_id : '',
            'daybookFactorySubCategoryIdDefault' => $entry->sub_category_id !== null ? (string) $entry->sub_category_id : '',
            'daybookFactoryUnitDefault' => $entry->unit ?? '',
            'daybookFactoryQuantityDefault' => $entry->quantity !== null ? (string) $entry->quantity : '',
            'daybookFactoryUnitPriceDefault' => $entry->unit_price !== null ? number_format((float) $entry->unit_price, 2, '.', '') : '',
            'daybookEntryDate' => $entry->entry_date->format('Y-m-d'),
            'daybookTypeDefault' => $entry->type,
            'daybookAmountDefault' => number_format((float) $entry->amount, 2, '.', ''),
            'daybookDescriptionDefault' => $entry->description ?? '',
            'daybookPaymentMethodDefault' => $entry->payment_method ?? DayBookEntry::PAYMENT_CASH,
            'daybookPaymentBankDefault' => $entry->payment_bank ?? '',
            'daybookPaymentReferenceDefault' => $entry->payment_reference ?? '',
            'daybookPaidByPartyIdDefault' => $entry->paid_by_party_id !== null ? (string) $entry->paid_by_party_id : '',
            'daybookPurchaseFileIdDefault' => $entry->purchase_file_id !== null ? (string) $entry->purchase_file_id : '',
            'daybookSoldAreaQtyDefault' => $entry->sold_area_qty !== null ? rtrim(rtrim(number_format((float) $entry->sold_area_qty, 4, '.', ''), '0'), '.') : '',
            'daybookSoldAreaUnitDefault' => $entry->sold_area_unit ?: 'marla',
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
                'paid_by_party_id' => ['nullable', 'integer', Rule::exists('parties', 'id')],
                'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
                'purchase_file_id' => ['nullable', 'integer', Rule::exists('purchase_files', 'id')],
                'sold_area_qty' => ['nullable', 'numeric', 'min:0'],
                'sold_area_unit' => ['nullable', 'string', Rule::in(['marla', 'kanal', 'acre', 'sqft'])],
                'party_id' => ['nullable', 'integer', Rule::exists('parties', 'id')],
                'party_sub_category_id' => ['nullable', 'integer', Rule::exists('party_sub_categories', 'id')],
                // Factory-only (Construction & Material)
                'sub_category_id' => ['nullable', 'integer', Rule::exists('party_sub_categories', 'id')],
                'unit' => ['nullable', 'string', 'max:50'],
                'quantity' => ['nullable', 'integer', 'min:1'],
                'unit_price' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/', 'numeric', 'min:0.01'],
                'link_type' => ['nullable', 'in:office,project,land,plot,factory,customer,party'],
                'link_id' => ['nullable', 'integer', 'min:1'],
            ],
            [
                'project_id.exists' => 'The selected project is invalid.',
                'purchase_file_id.exists' => 'The selected sale file is invalid.',
                'party_id.exists' => 'The selected party is invalid.',
                'paid_by_party_id.exists' => 'The selected paid-by party is invalid.',
                'party_sub_category_id.exists' => 'The selected sub category is invalid.',
                'sub_category_id.exists' => 'The selected sub category is invalid.',
                'payment_bank.in' => 'Please choose a bank from the list.',
                'sold_area_unit.in' => 'Choose a valid area unit.',
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

        $isFactoryExpense = false;
        if (! empty($contextProjectId)) {
            $project = Project::query()->with('landType')->find($contextProjectId);
            $lt = $project?->landType?->name;
            $isFactoryExpense = $lt !== null && mb_strtolower(trim($lt)) === 'factory';
        }

        if ($isFactoryExpense) {
            $request->validate([
                'sub_category_id' => ['required', 'integer', Rule::exists('party_sub_categories', 'id')],
                'quantity' => ['required', 'integer', 'min:1'],
                'unit_price' => ['required', 'regex:/^\d+(\.\d{1,2})?$/', 'numeric', 'min:0.01'],
            ], [], [
                'sub_category_id' => 'sub category',
                'unit_price' => 'unit price',
            ]);

            $sc = PartySubCategory::query()
                ->where('id', (int) $validated['sub_category_id'])
                ->whereHas('category', fn ($q) => $q->where('name', 'Construction & Material'))
                ->first();
            if (! $sc) {
                return back()
                    ->withErrors(['sub_category_id' => 'Please choose a sub category from Construction & Material.'])
                    ->withInput();
            }

            $validated['unit'] = $sc->unit;
            $validated['quantity'] = (int) $validated['quantity'];
            $validated['unit_price'] = number_format((float) $validated['unit_price'], 2, '.', '');
            $expected = round(((int) $validated['quantity']) * ((float) $validated['unit_price']), 2);
            $amount = round((float) $validated['amount'], 2);
            if (abs($expected - $amount) > 0.009) {
                return back()
                    ->withErrors(['amount' => 'Amount must equal Quantity × Unit Price.'])
                    ->withInput();
            }
        } else {
            $validated['sub_category_id'] = null;
            $validated['unit'] = null;
            $validated['quantity'] = null;
            $validated['unit_price'] = null;
        }

        DB::transaction(function () use ($validated, $contextProjectId, $entry) {
            $validated = $this->applyPurchaseFileToEntry($validated, $contextProjectId, (int) $entry->id);
            $validated = $this->normalizePaymentSettlement($validated);
            $entry->update($validated);
        });

        return redirect()
            ->route('daybook.show', $entry)
            ->with('success', 'DayBook entry updated.');
    }

    public function destroy(DayBookEntry $entry)
    {
        DB::transaction(function () use ($entry) {
            $entry->delete();
        });

        return redirect()->route('daybook.index')->with('success', 'Entry deleted.');
    }
}
