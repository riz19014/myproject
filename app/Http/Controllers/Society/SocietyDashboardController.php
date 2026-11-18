<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Society\Concerns\InteractsWithSocietyProject;
use App\Models\DayBookEntry;
use App\Models\DevelopmentPackage;
use App\Models\PlotBooking;
use App\Models\Project;
use App\Models\ProjectLandAllocation;
use App\Models\ProjectPlot;
use App\Support\LandMeasure;
use App\Support\SocietyProject;
use Illuminate\View\View;

class SocietyDashboardController extends Controller
{
    use InteractsWithSocietyProject;

    public function index(Project $project): View
    {
        $project = $this->societyProject($project);
        $project->load('landType');

        $purchasedMarla = SocietyProject::purchasedMarla($project);
        $allocatedMarla = (float) ProjectLandAllocation::query()
            ->where('project_id', $project->id)
            ->sum('land_area_marla');

        $allocations = ProjectLandAllocation::query()
            ->where('project_id', $project->id)
            ->get();

        $plotStats = ProjectPlot::query()
            ->where('project_id', $project->id)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $cashIn = (float) DayBookEntry::query()
            ->where('project_id', $project->id)
            ->where('type', DayBookEntry::TYPE_CASH_IN)
            ->sum('amount');
        $cashOut = (float) DayBookEntry::query()
            ->where('project_id', $project->id)
            ->where('type', DayBookEntry::TYPE_CASH_OUT)
            ->sum('amount');

        $purchaseCost = (float) $project->purchaseItems()->sum('line_total_rs');
        $bookings = PlotBooking::query()
            ->where('project_id', $project->id)
            ->whereNotIn('status', [PlotBooking::STATUS_CANCELLED, PlotBooking::STATUS_TRANSFERRED])
            ->with('installmentPlan.dues')
            ->get();

        $saleValue = (float) $bookings->sum('total_price');
        $collected = (float) $bookings->sum(fn (PlotBooking $b) => $b->paidTotal());
        $outstanding = (float) $bookings->sum(fn (PlotBooking $b) => $b->outstanding());

        $packages = DevelopmentPackage::query()
            ->where('project_id', $project->id)
            ->with('boqItems')
            ->get();

        return view('society.dashboard.index', [
            'project' => $project,
            'purchasedMarla' => $purchasedMarla,
            'purchasedLabel' => LandMeasure::formatAkmsLabelFromMarla($purchasedMarla),
            'allocatedMarla' => $allocatedMarla,
            'allocatedLabel' => LandMeasure::formatAkmsLabelFromMarla($allocatedMarla),
            'landVariance' => $purchasedMarla - $allocatedMarla,
            'allocations' => $allocations,
            'plotStats' => $plotStats,
            'cashIn' => $cashIn,
            'cashOut' => $cashOut,
            'netCash' => $cashIn - $cashOut,
            'purchaseCost' => $purchaseCost,
            'saleValue' => $saleValue,
            'collected' => $collected,
            'outstanding' => $outstanding,
            'profitEstimate' => $collected - $cashOut - $purchaseCost,
            'packages' => $packages,
            'pendingApprovals' => $project->societyApprovals()->where('status', 'pending')->count(),
        ]);
    }
}
