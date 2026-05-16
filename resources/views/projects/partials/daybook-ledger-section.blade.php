        @if($entries->isEmpty())
            <p class="text-muted mb-0">No daybook entries yet. Add them from <a href="{{ route('daybook.index') }}">Daybook</a> and choose this project (and optionally a party).</p>
        @else
            <div class="row g-2 mb-4">
                <div class="col-sm-4">
                    <div class="border rounded p-2 small bg-light h-100">
                        <span class="text-muted d-block">Total land (files)</span>
                        <span class="fw-semibold">Rs {{ number_format($ledgerProjectLandTotalRs, 2) }}</span>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="border rounded p-2 small bg-light h-100">
                        <span class="text-muted d-block">Total paid (DayBook)</span>
                        <span class="fw-semibold">Rs {{ number_format($ledgerTotalPaid, 2) }}</span>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="border rounded p-2 small bg-light h-100">
                        <span class="text-muted d-block">Balance payable</span>
                        @if($ledgerTotalPayable >= 0)
                            <span class="fw-semibold text-primary">Rs {{ number_format($ledgerTotalPayable, 2) }}</span>
                        @else
                            <span class="fw-semibold text-muted">Overpaid Rs {{ number_format(abs($ledgerTotalPayable), 2) }}</span>
                        @endif
                    </div>
                </div>
            </div>

            @foreach($ledgerSections as $section)
                @php
                    $hasLand = ($section['land_total_rs'] ?? 0) > 0;
                    $sectionPayable = $section['payable'] ?? null;
                    $sectionOverpaid = $hasLand && $sectionPayable !== null && $sectionPayable < 0;
                @endphp
                <div class="mb-4 pb-3 border-bottom">
                    <h6 class="mb-1 fw-semibold">{{ $section['heading'] }}</h6>
                    @if(!empty($section['subtitle']))
                        <p class="small text-muted mb-2">{{ $section['subtitle'] }}</p>
                    @endif

                    <div class="row g-2 mb-3">
                        @if($hasLand)
                            <div class="col-md-4 col-sm-6">
                                <div class="border rounded p-2 small bg-white">
                                    <span class="text-muted d-block">Land total (file)</span>
                                    <span class="fw-semibold">Rs {{ number_format($section['land_total_rs'], 2) }}</span>
                                    @if(!empty($section['land_area_label']))
                                        <span class="d-block text-muted mt-1">{{ $section['land_area_label'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="{{ $hasLand ? 'col-md-4 col-sm-6' : 'col-sm-6' }}">
                            <div class="border rounded p-2 small bg-white">
                                <span class="text-muted d-block">Total paid</span>
                                <span class="fw-semibold">Rs {{ number_format($section['total_paid'], 2) }}</span>
                            </div>
                        </div>
                        @if($hasLand)
                            <div class="col-md-4 col-sm-6">
                                <div class="border rounded p-2 small bg-white">
                                    <span class="text-muted d-block">Balance payable</span>
                                    @if($sectionOverpaid)
                                        <span class="fw-semibold text-muted">Overpaid Rs {{ number_format(abs($sectionPayable), 2) }}</span>
                                    @else
                                        <span class="fw-semibold text-primary">Rs {{ number_format($sectionPayable, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-theme mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Payment</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">{{ $hasLand ? 'Balance payable' : 'Paid so far' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($hasLand)
                                    <tr class="table-light">
                                        <td>—</td>
                                        <td>Land total (file)</td>
                                        <td>—</td>
                                        <td class="text-end font-monospace">Rs {{ number_format($section['land_total_rs'], 2) }}</td>
                                        <td class="text-end font-monospace fw-semibold">Rs {{ number_format($section['land_total_rs'], 2) }}</td>
                                    </tr>
                                @endif
                                @foreach($section['lines'] as $row)
                                    @php
                                        $e = $row['entry'];
                                        $rowPayable = $row['payable'];
                                        $rowOverpaid = $hasLand && $rowPayable !== null && $rowPayable < 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $e->entry_date->format('d M Y') }}</td>
                                        <td>{{ $e->description ?: '—' }}</td>
                                        <td>
                                            @if($e->type === 'cash_in')
                                                <span class="text-success">Payment in</span>
                                            @else
                                                <span class="text-danger">Payment out</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace">Rs {{ number_format((float) $e->amount, 2) }}</td>
                                        <td class="text-end font-monospace">
                                            @if($rowPayable === null)
                                                Rs {{ number_format($row['paid'], 2) }}
                                            @elseif($rowOverpaid)
                                                <span class="text-muted small">Overpaid Rs {{ number_format(abs($rowPayable), 2) }}</span>
                                            @else
                                                Rs {{ number_format($rowPayable, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="table-light fw-semibold">
                                    <td colspan="4" class="text-end">
                                        @if($hasLand)
                                            Balance payable — {{ $section['heading'] }}
                                        @else
                                            Total paid — {{ $section['heading'] }}
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace">
                                        @if($hasLand)
                                            @if($sectionOverpaid)
                                                <span class="text-muted">Overpaid Rs {{ number_format(abs($sectionPayable), 2) }}</span>
                                            @else
                                                Rs {{ number_format($sectionPayable, 2) }}
                                            @endif
                                        @else
                                            Rs {{ number_format($section['total_paid'], 2) }}
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
