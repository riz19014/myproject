<?php

namespace App\Support;

use App\Models\DayBookEntry;
use App\Models\DevelopmentPackage;
use App\Models\InstallmentDue;
use App\Models\PlotBooking;
use App\Models\Project;
use App\Models\ProjectBlock;
use App\Models\ProjectBudget;
use App\Models\ProjectLandAllocation;
use App\Models\ProjectPlot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class SocietyReports
{
    /**
     * Full society report pack for one project.
     *
     * @return array<string, mixed>
     */
    public static function build(Project $project): array
    {
        $project->loadMissing(['purchaseItems', 'landType']);

        $purchasedMarla = SocietyProject::purchasedMarla($project);
        $saleableMarla = SocietyProject::saleableMarla($project);
        $nonSaleableMarla = SocietyProject::nonSaleableMarla($project);
        $soldFileMarla = SocietyProject::soldFileMarla($project);
        $remainingSaleableMarla = SocietyProject::remainingLandMarla($project);

        $allocations = ProjectLandAllocation::query()
            ->where('project_id', $project->id)
            ->orderBy('category')
            ->get();

        $purchaseCost = (float) $project->purchaseItems->sum('line_total_rs');
        $purchaseRatePerMarla = $purchasedMarla > 1e-6 ? $purchaseCost / $purchasedMarla : 0.0;
        $purchaseRatePerKanal = $purchaseRatePerMarla * 20;

        $packages = DevelopmentPackage::query()
            ->where('project_id', $project->id)
            ->orderBy('name')
            ->get();

        $plots = ProjectPlot::query()
            ->where('project_id', $project->id)
            ->with('block')
            ->orderBy('plot_number')
            ->get();

        $plotStats = [
            'total' => $plots->count(),
            'available' => $plots->where('status', ProjectPlot::STATUS_AVAILABLE)->count(),
            'booked' => $plots->where('status', ProjectPlot::STATUS_BOOKED)->count(),
            'sold' => $plots->where('status', ProjectPlot::STATUS_SOLD)->count(),
            'cancelled' => $plots->where('status', ProjectPlot::STATUS_CANCELLED)->count(),
            'reserved' => 0, // status not used yet
        ];

        $plotsBySize = $plots
            ->groupBy(fn (ProjectPlot $p) => LandMeasure::formatAkmsLabelFromMarla((float) $p->land_area_marla))
            ->map(fn (Collection $group, string $label) => [
                'size' => $label,
                'count' => $group->count(),
                'marla' => (float) $group->sum('land_area_marla'),
            ])
            ->values();

        $blocks = ProjectBlock::query()
            ->where('project_id', $project->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $plotsByBlock = $blocks->map(function (ProjectBlock $block) use ($plots) {
            $group = $plots->where('project_block_id', $block->id);

            return [
                'block' => $block->name,
                'total' => $group->count(),
                'available' => $group->where('status', ProjectPlot::STATUS_AVAILABLE)->count(),
                'booked' => $group->where('status', ProjectPlot::STATUS_BOOKED)->count(),
                'sold' => $group->where('status', ProjectPlot::STATUS_SOLD)->count(),
                'cancelled' => $group->where('status', ProjectPlot::STATUS_CANCELLED)->count(),
            ];
        });

        $unblocked = $plots->whereNull('project_block_id');
        if ($unblocked->isNotEmpty()) {
            $plotsByBlock = $plotsByBlock->push([
                'block' => 'No block',
                'total' => $unblocked->count(),
                'available' => $unblocked->where('status', ProjectPlot::STATUS_AVAILABLE)->count(),
                'booked' => $unblocked->where('status', ProjectPlot::STATUS_BOOKED)->count(),
                'sold' => $unblocked->where('status', ProjectPlot::STATUS_SOLD)->count(),
                'cancelled' => $unblocked->where('status', ProjectPlot::STATUS_CANCELLED)->count(),
            ]);
        }

        $bookings = PlotBooking::query()
            ->where('project_id', $project->id)
            ->with(['plot.block', 'customer', 'party', 'installmentPlan.dues', 'dayBookEntries'])
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->get();

        $activeBookings = $bookings->filter(
            fn (PlotBooking $b) => ! in_array($b->status, [PlotBooking::STATUS_CANCELLED, PlotBooking::STATUS_TRANSFERRED], true)
        );
        $cancelledBookings = $bookings->where('status', PlotBooking::STATUS_CANCELLED);
        $transferredBookings = $bookings->where('status', PlotBooking::STATUS_TRANSFERRED);

        $saleValue = (float) $activeBookings->sum('total_price');
        $received = (float) $activeBookings->sum(fn (PlotBooking $b) => $b->paidTotal());
        $outstanding = (float) $activeBookings->sum(fn (PlotBooking $b) => $b->outstanding());

        $salesByCustomer = $activeBookings
            ->groupBy(fn (PlotBooking $b) => $b->customer_id ?: ('party-'.$b->party_id))
            ->map(function (Collection $group) {
                /** @var PlotBooking $first */
                $first = $group->first();
                $name = $first->customer?->name ?? $first->party?->name ?? '—';

                return [
                    'customer' => $name,
                    'files' => $group->count(),
                    'sale_value' => (float) $group->sum('total_price'),
                    'received' => (float) $group->sum(fn (PlotBooking $b) => $b->paidTotal()),
                    'outstanding' => (float) $group->sum(fn (PlotBooking $b) => $b->outstanding()),
                ];
            })
            ->sortByDesc('sale_value')
            ->values();

        $salesByMonth = $activeBookings
            ->groupBy(fn (PlotBooking $b) => optional($b->booking_date)->format('Y-m') ?: 'unknown')
            ->map(fn (Collection $group, string $month) => [
                'month' => $month,
                'count' => $group->count(),
                'sale_value' => (float) $group->sum('total_price'),
                'received' => (float) $group->sum(fn (PlotBooking $b) => $b->paidTotal()),
            ])
            ->sortKeysDesc()
            ->values();

        $dues = InstallmentDue::query()
            ->whereHas('plan.booking', fn ($q) => $q->where('project_id', $project->id)
                ->whereNotIn('status', [PlotBooking::STATUS_CANCELLED, PlotBooking::STATUS_TRANSFERRED]))
            ->with(['plan.booking.plot', 'plan.booking.customer'])
            ->orderBy('due_date')
            ->get();

        $today = Carbon::today();
        $duePending = $dues->filter(fn (InstallmentDue $d) => $d->outstanding() > 0.009);
        $overdue = $duePending->filter(
            fn (InstallmentDue $d) => $d->due_date && $d->due_date->lt($today)
        );

        $cashIn = (float) DayBookEntry::query()
            ->where('project_id', $project->id)
            ->where('type', DayBookEntry::TYPE_CASH_IN)
            ->sum('amount');
        $cashOut = (float) DayBookEntry::query()
            ->where('project_id', $project->id)
            ->where('type', DayBookEntry::TYPE_CASH_OUT)
            ->sum('amount');

        $landCashOut = (float) DayBookEntry::query()
            ->where('project_id', $project->id)
            ->where('type', DayBookEntry::TYPE_CASH_OUT)
            ->landAcquisition()
            ->sum('amount');
        $developmentCashOut = (float) DayBookEntry::query()
            ->where('project_id', $project->id)
            ->where('type', DayBookEntry::TYPE_CASH_OUT)
            ->excludingLandAcquisition()
            ->sum('amount');

        $collectionByMethod = DayBookEntry::query()
            ->where('project_id', $project->id)
            ->where('type', DayBookEntry::TYPE_CASH_IN)
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as line_count')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'method' => self::paymentMethodLabel($row->payment_method),
                'total' => (float) $row->total,
                'lines' => (int) $row->line_count,
            ]);

        $budgets = ProjectBudget::query()
            ->where('project_id', $project->id)
            ->with('partySubCategory')
            ->orderBy('category')
            ->get();

        $expenseByBudget = $budgets->map(function (ProjectBudget $budget) use ($project) {
            $actual = 0.0;
            if ($budget->party_sub_category_id) {
                $actual = (float) DayBookEntry::query()
                    ->where('project_id', $project->id)
                    ->where('type', DayBookEntry::TYPE_CASH_OUT)
                    ->excludingLandAcquisition()
                    ->where('party_sub_category_id', $budget->party_sub_category_id)
                    ->sum('amount');
            }

            return [
                'category' => $budget->category,
                'estimated' => (float) $budget->estimated_amount,
                'actual' => $actual,
            ];
        });

        $investment = $purchaseCost + $cashOut;
        $profitability = $cashIn - $investment;
        $profitOnReceived = $received - $purchaseCost - $developmentCashOut;

        $avgSaleRatePerMarla = $soldFileMarla > 1e-6 ? $saleValue / $soldFileMarla : 0.0;
        $developmentPerKanal = $purchasedMarla > 1e-6
            ? $developmentCashOut / ($purchasedMarla / 20)
            : 0.0;

        $availablePlots = $plots->where('status', ProjectPlot::STATUS_AVAILABLE);
        $unsoldValuationListed = (float) $availablePlots->sum(fn (ProjectPlot $p) => (float) ($p->list_price ?? 0));
        $unsoldValuationAtAvg = $remainingSaleableMarla > 0 && $avgSaleRatePerMarla > 0
            ? $remainingSaleableMarla * $avgSaleRatePerMarla
            : $unsoldValuationListed;

        $landLedger = self::buildLandBalanceLedger(
            $purchasedMarla,
            $allocations,
            $activeBookings
        );

        return [
            'land' => [
                'purchased_marla' => $purchasedMarla,
                'purchased_label' => LandMeasure::formatAkmsLabelFromMarla($purchasedMarla),
                'purchase_cost' => $purchaseCost,
                'purchase_rate_per_marla' => $purchaseRatePerMarla,
                'purchase_rate_per_kanal' => $purchaseRatePerKanal,
                'allocations' => $allocations,
                'saleable_marla' => $saleableMarla,
                'saleable_label' => LandMeasure::formatAkmsLabelFromMarla($saleableMarla),
                'non_saleable_marla' => $nonSaleableMarla,
                'non_saleable_label' => LandMeasure::formatAkmsLabelFromMarla($nonSaleableMarla),
                'sold_marla' => $soldFileMarla,
                'sold_label' => LandMeasure::formatAkmsLabelFromMarla($soldFileMarla),
                'remaining_marla' => $remainingSaleableMarla,
                'remaining_label' => LandMeasure::formatAkmsLabelFromMarla(abs($remainingSaleableMarla)),
                'packages' => $packages,
                'ledger' => $landLedger,
            ],
            'plots' => [
                'stats' => $plotStats,
                'rows' => $plots,
                'by_size' => $plotsBySize,
                'by_block' => $plotsByBlock,
            ],
            'sales' => [
                'register' => $activeBookings,
                'by_customer' => $salesByCustomer,
                'by_month' => $salesByMonth,
                'cancelled' => $cancelledBookings,
                'transferred' => $transferredBookings,
                'all' => $bookings,
            ],
            'payments' => [
                'sale_value' => $saleValue,
                'received' => $received,
                'outstanding' => $outstanding,
                'dues' => $duePending,
                'overdue' => $overdue,
                'overdue_total' => (float) $overdue->sum(fn (InstallmentDue $d) => $d->outstanding()),
                'collection_by_method' => $collectionByMethod,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
            ],
            'expenses' => [
                'land_purchase_cost' => $purchaseCost,
                'land_cash_out' => $landCashOut,
                'development_cash_out' => $developmentCashOut,
                'budgets' => $expenseByBudget,
                'investment' => $investment,
                'profitability' => $profitability,
                'profit_on_received' => $profitOnReceived,
            ],
            'management' => [
                'purchased_label' => LandMeasure::formatAkmsLabelFromMarla($purchasedMarla),
                'saleable_label' => LandMeasure::formatAkmsLabelFromMarla($saleableMarla),
                'plot_total' => $plotStats['total'],
                'plot_sold' => $plotStats['sold'] + $plotStats['booked'],
                'investment' => $investment,
                'sale_value' => $saleValue,
                'received' => $received,
                'outstanding' => $outstanding,
                'development_per_kanal' => $developmentPerKanal,
                'avg_sale_rate_per_marla' => $avgSaleRatePerMarla,
                'net_profit' => $profitOnReceived,
                'unsold_valuation' => $unsoldValuationAtAvg,
                'remaining_label' => LandMeasure::formatAkmsLabelFromMarla(abs($remainingSaleableMarla)),
            ],
        ];
    }

    private static function paymentMethodLabel(?string $method): string
    {
        return match ($method) {
            DayBookEntry::PAYMENT_CASH, null, '' => 'Cash',
            DayBookEntry::PAYMENT_ONLINE => 'Online / Bank',
            DayBookEntry::PAYMENT_CHEQUE => 'Cheque',
            DayBookEntry::PAYMENT_PAYORDER => 'Pay order',
            DayBookEntry::PAYMENT_CASH_DEPOSIT => 'Cash deposit',
            DayBookEntry::PAYMENT_ADJUSTMENT => 'Adjustment',
            default => ucfirst((string) $method),
        };
    }

    /**
     * Running land balance ledger:
     * purchased → − non-saleable (roads etc.) → − each sold/booked file → remaining.
     *
     * @param  Collection<int, ProjectLandAllocation>  $allocations
     * @param  Collection<int, PlotBooking>  $activeBookings
     * @return list<array<string, mixed>>
     */
    private static function buildLandBalanceLedger(
        float $purchasedMarla,
        Collection $allocations,
        Collection $activeBookings
    ): array {
        $rows = [];
        $balance = round($purchasedMarla, 6);
        $seq = 0;

        $rows[] = self::ledgerRow(
            ++$seq,
            'opening',
            null,
            'Purchased land (project total)',
            $purchasedMarla,
            0.0,
            $balance
        );

        $nonSaleableRows = $allocations
            ->filter(fn (ProjectLandAllocation $a) => ! $a->isSaleable() && (float) $a->land_area_marla > 1e-6)
            ->values();

        foreach ($nonSaleableRows as $allocation) {
            $out = round((float) $allocation->land_area_marla, 6);
            $balance = round($balance - $out, 6);
            $rows[] = self::ledgerRow(
                ++$seq,
                'non_saleable',
                null,
                'Non-saleable — '.$allocation->categoryLabel(),
                0.0,
                $out,
                $balance
            );
        }

        if ($nonSaleableRows->isEmpty() && $purchasedMarla > 1e-6) {
            // Keep ledger clear even when master plan has no non-saleable lines yet.
            $rows[] = self::ledgerRow(
                ++$seq,
                'note',
                null,
                'Non-saleable (roads, parks, etc.) — none allocated',
                0.0,
                0.0,
                $balance
            );
        }

        $rows[] = self::ledgerRow(
            ++$seq,
            'saleable',
            null,
            'Saleable balance (after non-saleable)',
            0.0,
            0.0,
            $balance,
            true
        );

        $fileBookings = $activeBookings
            ->sortBy([
                fn (PlotBooking $b) => optional($b->booking_date)->format('Y-m-d') ?? '9999-99-99',
                fn (PlotBooking $b) => $b->id,
            ])
            ->values();

        foreach ($fileBookings as $booking) {
            $out = round((float) ($booking->plot?->land_area_marla ?? 0), 6);
            if ($out <= 1e-6) {
                continue;
            }
            $balance = round($balance - $out, 6);
            $customer = $booking->customer?->name ?? $booking->party?->name ?? '—';
            $plotNo = $booking->plot?->plot_number ?? ('#'.$booking->id);
            $rows[] = self::ledgerRow(
                ++$seq,
                'file',
                $booking->booking_date?->format('j M Y'),
                'File '.$plotNo.' — '.$customer.' ('.$booking->statusLabel().')',
                0.0,
                $out,
                $balance,
                false,
                $booking->id
            );
        }

        if ($fileBookings->isEmpty()) {
            $rows[] = self::ledgerRow(
                ++$seq,
                'note',
                null,
                'No sold / booked files yet',
                0.0,
                0.0,
                $balance
            );
        }

        $rows[] = self::ledgerRow(
            ++$seq,
            'closing',
            null,
            'Remaining saleable land balance',
            0.0,
            0.0,
            $balance,
            true
        );

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private static function ledgerRow(
        int $seq,
        string $kind,
        ?string $date,
        string $particulars,
        float $inMarla,
        float $outMarla,
        float $balanceMarla,
        bool $isSubtotal = false,
        ?int $bookingId = null
    ): array {
        return [
            'seq' => $seq,
            'kind' => $kind,
            'date' => $date,
            'particulars' => $particulars,
            'in_marla' => $inMarla,
            'out_marla' => $outMarla,
            'balance_marla' => $balanceMarla,
            'in_label' => $inMarla > 1e-6 ? LandMeasure::formatAkmsLabelFromMarla($inMarla) : '—',
            'out_label' => $outMarla > 1e-6 ? LandMeasure::formatAkmsLabelFromMarla($outMarla) : '—',
            'balance_label' => LandMeasure::formatAkmsLabelFromMarla(abs($balanceMarla)),
            'balance_negative' => $balanceMarla < -1e-6,
            'is_subtotal' => $isSubtotal,
            'booking_id' => $bookingId,
        ];
    }
}
