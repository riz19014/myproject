@php
    $sizeBytes = \Illuminate\Support\Facades\Storage::disk('public')->exists($doc->file_path)
        ? (int) \Illuminate\Support\Facades\Storage::disk('public')->size($doc->file_path)
        : 0;
    $sizeLabel = $sizeBytes < 1024
        ? $sizeBytes.' B'
        : (round($sizeBytes / 1024, 1).' KB');
@endphp
<tr data-doc-id="{{ $doc->id }}">
    <td>
        <i class="bi bi-file-earmark pf-doc-icon" aria-hidden="true"></i>
        {{ $doc->name ?? 'Document' }}
    </td>
    <td class="small text-muted">{{ $sizeLabel }}</td>
    <td class="small text-muted">{{ $doc->created_at?->format('d M Y, H:i') ?? '—' }}</td>
    <td class="text-end text-nowrap">
        <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-theme me-1" title="Open">
            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
        </a>
        <form action="{{ route('purchase.files.documents.destroy', [$purchase_file, $doc->id]) }}" method="post" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-title="Remove document?" title="Remove">
                <i class="bi bi-trash" aria-hidden="true"></i>
            </button>
        </form>
    </td>
</tr>
