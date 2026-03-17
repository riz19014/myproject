@extends('layouts.app')

@section('title', 'DayBook Entry')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Entry #{{ $entry->id }}</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('daybook.edit', $entry) }}" class="btn btn-outline-theme">Edit</a>
        <a href="{{ route('daybook.index') }}" class="btn btn-outline-theme">Back to DayBook</a>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-theme">
            <tr><th width="180">Date</th><td>{{ $entry->entry_date->format('d M Y') }}</td></tr>
            <tr><th>Type</th><td>{{ $entry->type === 'cash_in' ? 'Cash In' : 'Cash Out' }}</td></tr>
            <tr><th>Amount</th><td>{{ number_format($entry->amount) }}</td></tr>
            <tr><th>Description</th><td>{{ $entry->description ?? '—' }}</td></tr>
            <tr><th>Linked To</th><td>{{ $entry->getLinkLabel() }}</td></tr>
        </table>
    </div>
</div>
@endsection
