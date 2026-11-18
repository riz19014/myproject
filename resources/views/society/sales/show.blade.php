@extends('layouts.app')

@section('title', 'Sales file — '.($booking->plot?->plot_number ?? '#'.$booking->id))

@section('content')
@include('society.partials.nav')

@php
    $fileNo = $booking->plot?->plot_number ?? ('#'.$booking->id);
    $buyer = $booking->party?->name ?: ($booking->customer?->name ?? '—');
    $docsCount = (int) ($booking->documents_count ?? 0);
@endphp

<div class="mb-4">
    <h1 class="h3 mb-1">Sales file {{ $fileNo }}</h1>
    <p class="text-muted mb-0">{{ $buyer }}</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<style>
    .society-sales-widgets {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        max-width: 36rem;
    }
    @media (max-width: 575.98px) {
        .society-sales-widgets {
            grid-template-columns: 1fr;
        }
    }
    .society-sales-widget {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        min-height: 9.5rem;
        padding: 1.5rem 1rem;
        text-decoration: none;
        color: inherit;
        border: 1px solid var(--bs-border-color, #dee2e6);
        border-radius: 0.75rem;
        background: var(--bs-body-bg, #fff);
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    .society-sales-widget:hover {
        border-color: var(--bs-secondary-color, #6c757d);
        box-shadow: 0 0.35rem 1rem rgba(0, 0, 0, 0.06);
        transform: translateY(-1px);
        color: inherit;
    }
    .society-sales-widget__icon {
        font-size: 2rem;
        line-height: 1;
        opacity: 0.9;
    }
    .society-sales-widget__label {
        font-weight: 600;
        font-size: 1rem;
    }
    .society-sales-widget__meta {
        font-size: 0.8rem;
        color: var(--bs-secondary-color, #6c757d);
    }
</style>

<div class="society-sales-widgets">
    <a href="{{ route('society.sales.payment-sheet', [$project, $booking]) }}" class="society-sales-widget">
        <i class="bi bi-receipt society-sales-widget__icon" aria-hidden="true"></i>
        <span class="society-sales-widget__label">Payment sheet</span>
    </a>
    <a href="{{ route('society.sales.documents', [$project, $booking]) }}" class="society-sales-widget">
        <i class="bi bi-folder2-open society-sales-widget__icon" aria-hidden="true"></i>
        <span class="society-sales-widget__label">Documents</span>
        @if($docsCount > 0)
            <span class="society-sales-widget__meta">{{ $docsCount }} {{ $docsCount === 1 ? 'file' : 'files' }}</span>
        @endif
    </a>
</div>
@endsection
