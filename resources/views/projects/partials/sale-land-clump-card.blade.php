@php
    $collective = $collective ?? [];
    $allBatchesForSelect = $allBatchesForSelect ?? [];
    $currentBatchId = !empty($collective['batch_id']) ? (int) $collective['batch_id'] : null;
@endphp
<div class="sale-land-clump {{ !empty($collective['is_open']) ? 'is-open' : 'is-done' }}">
    <div class="sale-land-clump__top">
        <div>
            <div class="sale-land-clump__label">Sale file clump</div>
            <h3 class="sale-land-clump__title">{{ $collective['name'] }}</h3>
        </div>
        <span class="sale-land-clump__badge {{ !empty($collective['is_open']) ? 'is-open' : 'is-done' }}">
            {{ !empty($collective['is_open']) ? 'Open' : 'Completed' }}
        </span>
    </div>
    <div class="sale-land-clump__meta">
        {{ $collective['file_count'] }} {{ ($collective['file_count'] ?? 0) === 1 ? 'file' : 'files' }}
        · {{ $collective['total_land_sheet'] ?? ($collective['total_land_area'] ?? '—') }}
        @if(!empty($collective['batch_name']))
            · Batch: {{ $collective['batch_name'] }}
        @endif
    </div>
    @if(!empty($collective['exemption_summary']) && $collective['exemption_summary'] !== '—')
        <div class="sale-land-clump__exemption">{{ $collective['exemption_summary'] }}</div>
    @endif
    <div class="sale-land-clump__files">
        @foreach(($collective['files'] ?? []) as $file)
            <a href="{{ route('purchase.files.show', $file['id']) }}" class="sale-land-clump__chip" title="Open purchase file">
                {{ $file['name'] }}
            </a>
        @endforeach
    </div>
    <form method="post"
          action="{{ route('projects.sale-land.collectives.batch', [$project, $collective['id']]) }}"
          class="sale-land-clump__batch-form">
        @csrf
        <label class="sale-land-clump__batch-label" for="clump-batch-{{ $collective['id'] }}">Assign to batch</label>
        <div class="sale-land-clump__batch-row">
            <select
                id="clump-batch-{{ $collective['id'] }}"
                name="batch_id"
                class="form-select form-select-sm"
                @disabled($allBatchesForSelect === [])
            >
                <option value="">{{ $allBatchesForSelect === [] ? 'Create a batch first' : '— Not in a batch —' }}</option>
                @foreach($allBatchesForSelect as $batchOption)
                    <option value="{{ $batchOption['id'] }}" @selected($currentBatchId === (int) $batchOption['id'])>
                        {{ $batchOption['name'] }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-outline-theme" @disabled($allBatchesForSelect === [])>
                Save
            </button>
        </div>
    </form>
    <div class="sale-land-clump__actions">
        <a href="{{ route('sale.files.index', $project) }}#collective-{{ $collective['id'] }}" class="btn btn-sm btn-pink">
            View clump
        </a>
        <form method="post"
              action="{{ route('projects.sale-land.collectives.destroy', [$project, $collective['id']]) }}"
              class="d-inline">
            @csrf
            @method('DELETE')
            <button
                type="button"
                class="btn btn-sm btn-outline-danger btn-delete-confirm"
                data-title="Delete clump?"
                data-text="Delete &quot;{{ $collective['name'] }}&quot; and move its {{ (int) ($collective['file_count'] ?? 0) }} file{{ (int) ($collective['file_count'] ?? 0) === 1 ? '' : 's' }} back to Sale land (Ready to move)?"
                data-confirm="Yes, delete clump"
            >Delete</button>
        </form>
    </div>
</div>
