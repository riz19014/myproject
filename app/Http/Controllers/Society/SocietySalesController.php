<?php

namespace App\Http\Controllers\Society;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Society\Concerns\InteractsWithSocietyProject;
use App\Models\Customer;
use App\Models\DayBookEntry;
use App\Models\InstallmentDue;
use App\Models\InstallmentPlan;
use App\Models\PlotBooking;
use App\Models\PlotBookingDocument;
use App\Models\Project;
use App\Models\ProjectPlot;
use App\Models\Sale;
use App\Models\SaleParticipant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SocietySalesController extends Controller
{
    use InteractsWithSocietyProject;

    public function index(Project $project): View
    {
        $project = $this->societyProject($project);
        $project->load('landType');

        $bookings = PlotBooking::query()
            ->where('project_id', $project->id)
            ->whereNotIn('status', [PlotBooking::STATUS_CANCELLED, PlotBooking::STATUS_TRANSFERRED])
            ->with([
                'plot.block',
                'customer',
                'party',
                'sale',
                'installmentPlan.dues',
                'dayBookEntries' => fn ($q) => $q
                    ->where('type', DayBookEntry::TYPE_CASH_IN)
                    ->orderBy('entry_date')
                    ->orderBy('id'),
            ])
            ->orderBy('id')
            ->get();

        // Backfill links and keep installment remaining = sale − daybook received.
        foreach ($bookings as $booking) {
            $this->refreshBookingReceipts($booking);
        }

        $bookings->load([
            'installmentPlan.dues',
            'dayBookEntries' => fn ($q) => $q
                ->where('type', DayBookEntry::TYPE_CASH_IN)
                ->orderBy('entry_date')
                ->orderBy('id'),
        ]);

        return view('society.sales.index', [
            'project' => $project,
            'bookings' => $bookings,
        ]);
    }

    /**
     * Attach project+party "Land sale" Payment-in rows that were saved without plot_booking_id.
     */
    private function linkUnassignedLandSaleReceipts(PlotBooking $booking): void
    {
        if (! $booking->party_id || ! Schema::hasColumn('day_book_entries', 'plot_booking_id')) {
            return;
        }

        $plotNumber = $booking->plot?->plot_number;
        $query = DayBookEntry::query()
            ->where('project_id', $booking->project_id)
            ->where('type', DayBookEntry::TYPE_CASH_IN)
            ->where('link_type', DayBookEntry::LINK_PARTY)
            ->where('link_id', $booking->party_id)
            ->whereNull('plot_booking_id');

        $candidates = $query->get()->filter(function (DayBookEntry $e) use ($plotNumber) {
            $desc = mb_strtolower((string) $e->description);
            if ($plotNumber && str_contains($desc, mb_strtolower($plotNumber))) {
                return true;
            }

            return str_contains($desc, 'land sale');
        });

        if ($candidates->isEmpty()) {
            return;
        }

        DayBookEntry::query()
            ->whereIn('id', $candidates->pluck('id'))
            ->update(['plot_booking_id' => $booking->id]);

        if (! $booking->booking_day_book_entry_id) {
            $booking->update([
                'booking_day_book_entry_id' => $candidates->sortBy('id')->first()?->id,
            ]);
        }

        $booking->syncFromDaybookReceipts();
    }

    /**
     * Always refresh received + installment remaining from linked daybook Payment-in.
     */
    private function refreshBookingReceipts(PlotBooking $booking): void
    {
        $this->linkUnassignedLandSaleReceipts($booking);
        $booking->syncFromDaybookReceipts();
        $booking->unsetRelation('installmentPlan');
        $booking->unsetRelation('dayBookEntries');
    }

    public function show(Project $project, PlotBooking $booking): View
    {
        $project = $this->societyProject($project);
        abort_unless((int) $booking->project_id === (int) $project->id, 404);

        $booking->load(['plot', 'customer', 'party']);
        $booking->loadCount('documents');

        return view('society.sales.show', [
            'project' => $project,
            'booking' => $booking,
        ]);
    }

    public function documents(Project $project, PlotBooking $booking): View
    {
        $project = $this->societyProject($project);
        abort_unless((int) $booking->project_id === (int) $project->id, 404);

        $booking->load(['plot', 'customer', 'party', 'documents' => fn ($q) => $q->orderByDesc('id')]);

        return view('society.sales.documents', [
            'project' => $project,
            'booking' => $booking,
        ]);
    }

    public function storeDocuments(Request $request, Project $project, PlotBooking $booking): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $booking->project_id === (int) $project->id, 404);

        $request->validate([
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'max:51200'],
        ]);

        foreach ($request->file('documents') as $file) {
            $booking->addDocument($file);
        }

        return redirect()
            ->route('society.sales.documents', [$project, $booking])
            ->with('success', 'Document(s) uploaded.');
    }

    public function destroyDocument(Project $project, PlotBooking $booking, PlotBookingDocument $document): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $booking->project_id === (int) $project->id, 404);
        abort_unless((int) $document->plot_booking_id === (int) $booking->id, 404);

        $document->delete();

        return redirect()
            ->route('society.sales.documents', [$project, $booking])
            ->with('success', 'Document removed.');
    }

    public function edit(Project $project, PlotBooking $booking): View
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $booking->project_id === (int) $project->id, 404);

        $booking->load(['plot', 'customer', 'party']);

        return view('society.sales.edit', [
            'project' => $project,
            'booking' => $booking,
        ]);
    }

    public function update(Request $request, Project $project, PlotBooking $booking): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $booking->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'booking_date' => ['required', 'date'],
            'total_price' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'area_acre' => ['nullable', 'integer', 'min:0'],
            'area_kanal' => ['nullable', 'integer', 'min:0'],
            'area_marla' => ['nullable', 'integer', 'min:0'],
            'area_sqft' => ['nullable', 'integer', 'min:0'],
        ]);

        $booking->loadMissing(['plot', 'sale']);

        DB::transaction(function () use ($booking, $validated) {
            $booking->update([
                'booking_date' => $validated['booking_date'],
                'total_price' => round((float) $validated['total_price'], 2),
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($booking->plot) {
                $acre = (int) ($validated['area_acre'] ?? $booking->plot->area_acre);
                $kanal = (int) ($validated['area_kanal'] ?? $booking->plot->area_kanal);
                $marla = (int) ($validated['area_marla'] ?? $booking->plot->area_marla);
                $sqft = (int) ($validated['area_sqft'] ?? $booking->plot->area_sqft);
                $booking->plot->fill([
                    'area_acre' => $acre,
                    'area_kanal' => $kanal,
                    'area_marla' => $marla,
                    'area_sqft' => $sqft,
                    'list_price' => round((float) $validated['total_price'], 2),
                ]);
                $booking->plot->syncMarlaFromAkms();
                $booking->plot->save();

                if ($booking->sale) {
                    $booking->sale->update([
                        'area_acre' => $acre,
                        'area_kanal' => $kanal,
                        'area_marla' => $marla,
                        'area_sqft' => $sqft,
                        'land_area_marla' => $booking->plot->land_area_marla,
                        'total_amount' => round((float) $validated['total_price'], 2),
                    ]);
                }
            }
        });

        return redirect()
            ->route('society.sales.show', [$project, $booking])
            ->with('success', 'Sales file updated.');
    }

    public function destroy(Project $project, PlotBooking $booking): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $booking->project_id === (int) $project->id, 404);

        DB::transaction(function () use ($booking) {
            $booking->load(['plot', 'sale', 'installmentPlan.dues']);

            DayBookEntry::query()
                ->where('plot_booking_id', $booking->id)
                ->update(['plot_booking_id' => null]);

            $saleId = $booking->sale_id;
            $plot = $booking->plot;
            $isAutoSaleFile = $plot && str_starts_with((string) $plot->plot_number, 'SF-');

            $booking->delete();

            if ($saleId) {
                SaleParticipant::query()->where('sale_id', $saleId)->delete();
                Sale::query()->whereKey($saleId)->delete();
            }

            if ($plot) {
                if ($isAutoSaleFile) {
                    $plot->delete();
                } else {
                    $plot->update(['status' => ProjectPlot::STATUS_AVAILABLE]);
                }
            }
        });

        return redirect()
            ->route('society.sales', $project)
            ->with('success', 'Sales file deleted.');
    }

    public function paymentSheet(Project $project, PlotBooking $booking): View
    {
        $project = $this->societyProject($project);
        abort_unless((int) $booking->project_id === (int) $project->id, 404);

        $this->refreshBookingReceipts($booking);
        $booking->load([
            'plot',
            'customer',
            'party',
            'installmentPlan.dues',
            'dayBookEntries' => fn ($q) => $q
                ->where('type', DayBookEntry::TYPE_CASH_IN)
                ->orderBy('entry_date')
                ->orderBy('id'),
        ]);

        $outstanding = $booking->fileOutstandingAmount();

        return view('society.sales.payment-sheet', [
            'project' => $project,
            'booking' => $booking,
            'receipts' => $booking->fileDaybookReceipts(),
            'canIgnoreOutstanding' => $booking->canIgnoreOutstandingRounding($outstanding),
            'ignoreOutstandingMax' => PlotBooking::IGNORE_OUTSTANDING_MAX,
        ]);
    }

    /**
     * Waive a tiny rounding leftover (e.g. Rs 0.33) by lowering sale price to match received,
     * then mark booking completed / plot sold.
     */
    public function ignoreOutstanding(Request $request, Project $project, PlotBooking $booking): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $booking->project_id === (int) $project->id, 404);

        $this->refreshBookingReceipts($booking);
        $booking->refresh();

        $received = $booking->fileReceivedAmount();
        $outstanding = $booking->fileOutstandingAmount();

        if (! $booking->canIgnoreOutstandingRounding($outstanding)) {
            return back()->withErrors([
                'outstanding' => $outstanding <= 0.009
                    ? 'There is no outstanding amount to ignore.'
                    : 'Outstanding is larger than Rs '.number_format(PlotBooking::IGNORE_OUTSTANDING_MAX, 2).'. Only small rounding leftovers can be ignored.',
            ]);
        }

        if ($received <= 0) {
            return back()->withErrors([
                'outstanding' => 'Cannot ignore outstanding until a payment has been received.',
            ]);
        }

        DB::transaction(function () use ($booking, $received, $outstanding) {
            $oldPrice = (float) $booking->total_price;
            $noteLine = sprintf(
                'Ignored rounding leftover Rs %s (sale price adjusted %s → %s).',
                number_format($outstanding, 2, '.', ''),
                number_format($oldPrice, 2, '.', ''),
                number_format($received, 2, '.', '')
            );
            $notes = trim((string) $booking->notes);
            $booking->update([
                'total_price' => round($received, 2),
                'notes' => $notes === '' ? $noteLine : ($notes."\n".$noteLine),
            ]);
            $booking->refresh();
            $booking->load('plot');
            $booking->syncFromDaybookReceipts();
        });

        return redirect()
            ->route('society.sales.payment-sheet', [$project, $booking])
            ->with('success', 'Rounding leftover ignored. Sale price adjusted and file marked sold/completed.');
    }

    public function storeBooking(Request $request, Project $project): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();

        $validated = $request->validate([
            'project_plot_id' => ['required', 'integer', 'exists:project_plots,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'booking_date' => ['required', 'date'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'booking_amount' => ['nullable', 'numeric', 'min:0'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:120'],
            'first_due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'post_booking_to_daybook' => ['nullable', 'boolean'],
        ]);

        $plot = ProjectPlot::query()
            ->whereKey($validated['project_plot_id'])
            ->where('project_id', $project->id)
            ->firstOrFail();

        if (! $plot->isAvailable()) {
            return back()->with('error', 'Plot is not available.')->withInput();
        }

        $bookingAmount = (float) ($validated['booking_amount'] ?? 0);
        $totalPrice = (float) $validated['total_price'];
        $count = (int) $validated['installment_count'];
        $planTotal = max(0, $totalPrice - $bookingAmount);

        DB::transaction(function () use ($project, $plot, $validated, $bookingAmount, $totalPrice, $count, $planTotal, $request) {
            $entryId = null;
            if ($bookingAmount > 0 && $request->boolean('post_booking_to_daybook')) {
                $entry = DayBookEntry::query()->create([
                    'entry_date' => $validated['booking_date'],
                    'type' => DayBookEntry::TYPE_CASH_IN,
                    'amount' => $bookingAmount,
                    'description' => 'Plot '.$plot->plot_number.' booking receipt',
                    'project_id' => $project->id,
                    'payment_method' => DayBookEntry::PAYMENT_CASH,
                    'link_type' => 'customer',
                    'link_id' => (int) $validated['customer_id'],
                ]);
                $entryId = $entry->id;
            }

            $booking = PlotBooking::query()->create([
                'project_id' => $project->id,
                'project_plot_id' => $plot->id,
                'customer_id' => $validated['customer_id'],
                'status' => PlotBooking::STATUS_ACTIVE,
                'booking_date' => $validated['booking_date'],
                'total_price' => $totalPrice,
                'booking_amount' => $bookingAmount,
                'booking_day_book_entry_id' => $entryId,
                'notes' => $validated['notes'] ?? null,
            ]);

            $plot->update(['status' => ProjectPlot::STATUS_BOOKED]);

            if ($count > 0 && $planTotal > 0) {
                $plan = InstallmentPlan::query()->create([
                    'plot_booking_id' => $booking->id,
                    'name' => 'Installment plan',
                    'installment_count' => $count,
                    'total_amount' => $planTotal,
                ]);

                $base = floor(($planTotal / $count) * 100) / 100;
                $firstDue = Carbon::parse($validated['first_due_date']);
                $allocated = 0.0;
                for ($i = 1; $i <= $count; $i++) {
                    $amount = $i === $count
                        ? round($planTotal - $allocated, 2)
                        : $base;
                    $allocated += $amount;
                    InstallmentDue::query()->create([
                        'installment_plan_id' => $plan->id,
                        'sequence' => $i,
                        'label' => 'Installment '.$i,
                        'due_date' => $firstDue->copy()->addMonths($i - 1)->toDateString(),
                        'amount' => $amount,
                        'paid_amount' => 0,
                        'status' => InstallmentDue::STATUS_PENDING,
                    ]);
                }
            }
        });

        return back()->with('success', 'Plot booked successfully.');
    }

    public function payInstallment(Request $request, Project $project, InstallmentDue $due): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();

        $due->load('plan.booking.plot');
        abort_unless((int) $due->plan?->booking?->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:40'],
        ]);

        $pay = min((float) $validated['amount'], $due->outstanding());
        if ($pay <= 0) {
            return back()->with('error', 'Nothing outstanding on this installment.');
        }

        DB::transaction(function () use ($project, $due, $validated, $pay) {
            $booking = $due->plan->booking;
            $plotLabel = $booking->plot?->plot_number ?? ('Booking #'.$booking->id);
            $entry = DayBookEntry::query()->create([
                'entry_date' => $validated['entry_date'],
                'type' => DayBookEntry::TYPE_CASH_IN,
                'amount' => $pay,
                'description' => 'File '.$plotLabel.' '.$due->label.' receipt',
                'project_id' => $project->id,
                'payment_method' => $validated['payment_method'] ?? DayBookEntry::PAYMENT_CASH,
                'link_type' => $booking->party_id ? DayBookEntry::LINK_PARTY : 'customer',
                'link_id' => $booking->party_id ?: $booking->customer_id,
                'plot_booking_id' => $booking->id,
            ]);

            $due->day_book_entry_id = $entry->id;
            $due->save();

            if (! $booking->booking_day_book_entry_id) {
                $booking->update(['booking_day_book_entry_id' => $entry->id]);
            }

            // Shrink remaining installment dues from daybook totals (paid tracked as booking_amount).
            $booking->syncFromDaybookReceipts();
        });

        return back()->with('success', 'Installment payment recorded.');
    }

    public function cancel(Request $request, Project $project, PlotBooking $booking): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $booking->project_id === (int) $project->id, 404);

        if (in_array($booking->status, [PlotBooking::STATUS_CANCELLED, PlotBooking::STATUS_TRANSFERRED], true)) {
            return back()->with('error', 'Booking already closed.');
        }

        $validated = $request->validate([
            'cancel_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($booking, $validated) {
            $booking->update([
                'status' => PlotBooking::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => $validated['cancel_reason'] ?? null,
            ]);
            if ($booking->plot) {
                $booking->plot->update(['status' => ProjectPlot::STATUS_AVAILABLE]);
            }
        });

        return back()->with('success', 'Booking cancelled; plot is available again.');
    }

    public function transfer(Request $request, Project $project, PlotBooking $booking): RedirectResponse
    {
        $project = $this->societyProject($project);
        $this->authorizeSocietyManage();
        abort_unless((int) $booking->project_id === (int) $project->id, 404);

        if (! in_array($booking->status, [PlotBooking::STATUS_BOOKED, PlotBooking::STATUS_ACTIVE], true)) {
            return back()->with('error', 'Only active bookings can be transferred.');
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ((int) $validated['customer_id'] === (int) $booking->customer_id) {
            return back()->with('error', 'Select a different customer.');
        }

        DB::transaction(function () use ($project, $booking, $validated) {
            $booking->load('installmentPlan.dues');
            $oldPlan = $booking->installmentPlan;

            $booking->update([
                'status' => PlotBooking::STATUS_TRANSFERRED,
                'notes' => trim(($booking->notes ? $booking->notes."\n" : '').'Transferred to customer #'.$validated['customer_id']),
            ]);

            $newBooking = PlotBooking::query()->create([
                'project_id' => $project->id,
                'project_plot_id' => $booking->project_plot_id,
                'customer_id' => $validated['customer_id'],
                'status' => PlotBooking::STATUS_ACTIVE,
                'booking_date' => now()->toDateString(),
                'total_price' => $booking->total_price,
                'booking_amount' => $booking->booking_amount,
                'transferred_from_booking_id' => $booking->id,
                'notes' => $validated['notes'] ?? 'Transferred booking',
            ]);

            if ($oldPlan) {
                $newPlan = InstallmentPlan::query()->create([
                    'plot_booking_id' => $newBooking->id,
                    'name' => $oldPlan->name,
                    'installment_count' => $oldPlan->installment_count,
                    'total_amount' => $oldPlan->total_amount,
                ]);
                foreach ($oldPlan->dues as $due) {
                    InstallmentDue::query()->create([
                        'installment_plan_id' => $newPlan->id,
                        'sequence' => $due->sequence,
                        'label' => $due->label,
                        'due_date' => $due->due_date,
                        'amount' => $due->amount,
                        'paid_amount' => $due->paid_amount,
                        'status' => $due->status,
                        'day_book_entry_id' => $due->day_book_entry_id,
                        'paid_at' => $due->paid_at,
                    ]);
                }
            }
        });

        return back()->with('success', 'Booking transferred to new customer.');
    }
}
