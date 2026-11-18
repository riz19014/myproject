@extends('layouts.app')

@section('title', 'Sales — '.$project->name)

@section('content')
@include('society.partials.nav')

@php
    $canManage = auth()->user()?->canManageSociety();
@endphp

<div class="mb-4">
    <h1 class="h3 mb-1">Sales files</h1>
    <p class="text-muted mb-0">Files from Daybook land sales. Open a file or payment sheet for amounts and receipts.</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card card-theme">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-theme mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width:4rem">Sr</th>
                        <th>File #</th>
                        <th>Sale date</th>
                        <th>Buyer</th>
                        <th>Area</th>
                        <th class="text-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                        @php
                            $plot = $booking->plot;
                            $areaLabel = $plot
                                ? \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $plot->land_area_marla)
                                : '—';
                            $buyer = $booking->party?->name ?: ($booking->customer?->name ?? '—');
                            $fileNo = $plot?->plot_number ?? ('#'.$booking->id);
                        @endphp
                        <tr>
                            <td class="font-monospace">{{ $loop->iteration }}</td>
                            <td class="fw-semibold">
                                <a href="{{ route('society.sales.show', [$project, $booking]) }}">{{ $fileNo }}</a>
                            </td>
                            <td>{{ $booking->booking_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $buyer }}</td>
                            <td>{{ $areaLabel }}</td>
                            <td class="text-nowrap">
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="{{ route('society.sales.show', [$project, $booking]) }}" class="btn btn-sm btn-outline-theme">View</a>
                                    @if($canManage)
                                        <a href="{{ route('society.sales.edit', [$project, $booking]) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                                        <form method="post" action="{{ route('society.sales.destroy', [$project, $booking]) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-danger btn-delete-confirm"
                                                data-title="Delete sales file?"
                                                data-text="Delete {{ $fileNo }} ({{ $buyer }})? This cannot be undone."
                                                data-confirm="Yes, delete"
                                            >Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted text-center py-4">No sales files yet. Record a land sale from Daybook (Sale).</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
