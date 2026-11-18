<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\InstallmentDue;
use App\Models\InstallmentPlan;
use App\Models\Party;
use App\Models\PlotBooking;
use App\Models\Project;
use App\Models\ProjectPlot;
use App\Models\Sale;
use App\Models\SaleParticipant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Creates society sales "customer file" (plot + booking + installment sheet)
 * from a daybook free-form land sale to a party.
 */
final class SocietyLandSaleFile
{
    /**
     * @param  array{
     *     area_acre:int,
     *     area_kanal:int,
     *     area_marla:int,
     *     area_sqft:int,
     *     land_area_marla:float|int|string,
     *     total_amount:float|int|string,
     *     party_id:int,
     *     received_amount?:float|int|string|null,
     *     installment_count?:int|null,
     *     first_due_date?:string|null,
     *     booking_date?:string|null,
     *     notes?:string|null,
     * }  $data
     * @return array{sale: Sale, booking: PlotBooking, customer: Customer, plot: ProjectPlot}
     */
    public static function createFromDaybookSale(Project $project, array $data): array
    {
        return DB::transaction(function () use ($project, $data) {
            $party = Party::query()->findOrFail((int) $data['party_id']);
            $customer = self::resolveCustomerFromParty($party);

            $total = round((float) $data['total_amount'], 2);
            $received = round(max(0, (float) ($data['received_amount'] ?? 0)), 2);
            if ($received > $total) {
                $received = $total;
            }

            $acre = (int) $data['area_acre'];
            $kanal = (int) $data['area_kanal'];
            $marla = (int) $data['area_marla'];
            $sqft = (int) $data['area_sqft'];
            $landMarla = round((float) $data['land_area_marla'], 4);
            $bookingDate = Carbon::parse($data['booking_date'] ?? now()->toDateString())->toDateString();
            $installmentCount = max(1, (int) ($data['installment_count'] ?? 1));
            $firstDue = Carbon::parse($data['first_due_date'] ?? Carbon::parse($bookingDate)->addMonth()->toDateString());
            $notes = trim((string) ($data['notes'] ?? ''));
            $areaLabel = LandMeasure::formatAkmsLabelFromMarla($landMarla);

            $plotNumber = self::nextSaleFilePlotNumber($project->id);
            $plot = ProjectPlot::query()->create([
                'project_id' => $project->id,
                'project_block_id' => null,
                'plot_number' => $plotNumber,
                'category' => 'residential',
                'area_acre' => $acre,
                'area_kanal' => $kanal,
                'area_marla' => $marla,
                'area_sqft' => $sqft,
                'land_area_marla' => $landMarla,
                'status' => ProjectPlot::STATUS_BOOKED,
                'list_price' => $total,
                'notes' => 'Auto file from land sale · '.$areaLabel
                    .($notes !== '' ? ' · '.$notes : ''),
            ]);

            $sale = Sale::query()->create([
                'project_id' => $project->id,
                'project_file_id' => null,
                'project_plot_id' => $plot->id,
                'sale_type' => Sale::TYPE_DIRECT,
                'customer_id' => $customer->id,
                'area_acre' => $acre,
                'area_kanal' => $kanal,
                'area_marla' => $marla,
                'area_sqft' => $sqft,
                'land_area_marla' => $landMarla,
                'total_amount' => $total,
            ]);

            SaleParticipant::query()->create([
                'sale_id' => $sale->id,
                'party_id' => $party->id,
                'customer_id' => $customer->id,
            ]);

            $booking = PlotBooking::query()->create([
                'project_id' => $project->id,
                'project_plot_id' => $plot->id,
                'sale_id' => $sale->id,
                'customer_id' => $customer->id,
                'party_id' => $party->id,
                'status' => PlotBooking::STATUS_ACTIVE,
                'booking_date' => $bookingDate,
                'total_price' => $total,
                'booking_amount' => $received,
                'notes' => trim(
                    'Land sale file · '.$areaLabel
                    .' · Party: '.$party->name
                    .($notes !== '' ? "\n".$notes : '')
                ),
            ]);

            $planTotal = max(0, round($total - $received, 2));
            if ($planTotal > 0.009) {
                $plan = InstallmentPlan::query()->create([
                    'plot_booking_id' => $booking->id,
                    'name' => 'Payment schedule',
                    'installment_count' => $installmentCount,
                    'total_amount' => $planTotal,
                ]);

                $base = floor(($planTotal / $installmentCount) * 100) / 100;
                $allocated = 0.0;
                for ($i = 1; $i <= $installmentCount; $i++) {
                    $amount = $i === $installmentCount
                        ? round($planTotal - $allocated, 2)
                        : $base;
                    $allocated += $amount;
                    InstallmentDue::query()->create([
                        'installment_plan_id' => $plan->id,
                        'sequence' => $i,
                        'label' => $installmentCount === 1 ? 'Balance due' : 'Installment '.$i,
                        'due_date' => $firstDue->copy()->addMonths($i - 1)->toDateString(),
                        'amount' => $amount,
                        'paid_amount' => 0,
                        'status' => InstallmentDue::STATUS_PENDING,
                    ]);
                }
            } else {
                $booking->update(['status' => PlotBooking::STATUS_COMPLETED]);
                $plot->update(['status' => ProjectPlot::STATUS_SOLD]);
            }

            return [
                'sale' => $sale->fresh(),
                'booking' => $booking->fresh(['installmentPlan.dues', 'plot', 'customer']),
                'customer' => $customer,
                'plot' => $plot->fresh(),
            ];
        });
    }

    public static function resolveCustomerFromParty(Party $party): Customer
    {
        $cnic = trim((string) ($party->cnic ?? ''));
        if ($cnic !== '') {
            $byCnic = Customer::query()->where('cnic', $cnic)->first();
            if ($byCnic) {
                return $byCnic;
            }
        }

        $phone = trim((string) ($party->phone ?? ''));
        $name = trim((string) $party->name);
        if ($phone !== '') {
            $byPhone = Customer::query()
                ->where('phone', $phone)
                ->where('name', $name)
                ->first();
            if ($byPhone) {
                return $byPhone;
            }
        }

        $byName = Customer::query()->where('name', $name)->first();
        if ($byName) {
            if ($phone !== '' && empty($byName->phone)) {
                $byName->phone = $phone;
            }
            if ($cnic !== '' && empty($byName->cnic)) {
                $byName->cnic = $cnic;
            }
            if ($byName->isDirty()) {
                $byName->save();
            }

            return $byName;
        }

        return Customer::query()->create([
            'name' => $name !== '' ? $name : 'Customer',
            'phone' => $phone !== '' ? $phone : null,
            'cnic' => $cnic !== '' ? $cnic : null,
            'address' => $party->address ?: null,
            'notes' => 'Created from party #'.$party->id.' for society land sale',
        ]);
    }

    public static function nextSaleFilePlotNumber(int $projectId): string
    {
        $existing = ProjectPlot::query()
            ->where('project_id', $projectId)
            ->where('plot_number', 'like', 'SF-%')
            ->pluck('plot_number');

        $max = 0;
        foreach ($existing as $num) {
            if (preg_match('/^SF-(\d+)$/i', (string) $num, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'SF-'.str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
    }
}
