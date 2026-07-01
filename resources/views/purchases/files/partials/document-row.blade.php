@php
    $disk = $doc->storageDisk();
    $sizeBytes = $doc->file_path && \Illuminate\Support\Facades\Storage::disk($disk)->exists($doc->file_path)
        ? (int) \Illuminate\Support\Facades\Storage::disk($disk)->size($doc->file_path)
        : 0;
    if ($sizeBytes < 1024) {
        $sizeLabel = $sizeBytes.' B';
    } elseif ($sizeBytes < 1024 * 1024) {
        $sizeLabel = round($sizeBytes / 1024, 1).' KB';
    } else {
        $sizeLabel = round($sizeBytes / (1024 * 1024), 2).' MB';
    }
    $isProcessing = $doc->isProcessing();
    $statusLabel = match ($doc->status) {
        \App\Models\PurchaseFileDocument::STATUS_PENDING => 'Queued',
        \App\Models\PurchaseFileDocument::STATUS_PROCESSING => 'Processing',
        \App\Models\PurchaseFileDocument::STATUS_FAILED => 'Failed',
        default => null,
    };
@endphp
<tr data-doc-id="{{ $doc->id }}" @if($isProcessing) data-doc-processing="1" @endif>
    <td>
        <i class="bi bi-file-earmark pf-doc-icon" aria-hidden="true"></i>
        {{ $doc->name ?? 'Document' }}
        @if($statusLabel)
            <span class="badge rounded-pill text-bg-warning ms-1 pf-doc-status-badge">{{ $statusLabel }}</span>
        @endif
    </td>
    <td class="small text-muted">{{ $sizeLabel }}</td>
    <td class="small text-muted">{{ $doc->created_at?->format('d M Y, H:i') ?? '—' }}</td>
    <td class="text-end text-nowrap">
        @if($doc->isProcessed())
            <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-theme me-1" title="Open">
                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
            </a>
        @else
            <button type="button" class="btn btn-sm btn-outline-success me-1" disabled title="Saved">
                <i class="bi bi-check-lg" aria-hidden="true"></i>
            </button>
        @endif
        <form action="{{ route('purchase.files.documents.destroy', [$purchase_file, $doc->id]) }}" method="post" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-title="Remove document?" title="Remove">
                <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
        </form>
    </td>
</tr>
