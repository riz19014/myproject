<?php

namespace App\Jobs;

use App\Models\PurchaseFileDocument;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProcessPurchaseFileDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public PurchaseFileDocument $document) {}

    public function handle(): void
    {
        $document = $this->document->fresh();

        if (! $document || $document->isProcessed()) {
            return;
        }

        $document->update(['status' => PurchaseFileDocument::STATUS_PROCESSING]);

        $tempPath = $document->file_path;
        if (! Storage::disk('local')->exists($tempPath)) {
            $document->update([
                'status' => PurchaseFileDocument::STATUS_FAILED,
                'error_message' => 'Temporary file not found.',
            ]);

            return;
        }

        $safeName = Str::slug(pathinfo($document->name ?? 'document', PATHINFO_FILENAME));
        if ($safeName === '') {
            $safeName = 'document';
        }
        $extension = pathinfo($document->name ?? '', PATHINFO_EXTENSION);
        $finalName = $safeName.($extension !== '' ? '.'.$extension : '');
        $finalPath = 'purchase-files/'.$document->purchase_file_id.'/'.$document->id.'_'.$finalName;

        $stream = Storage::disk('local')->readStream($tempPath);
        Storage::disk('public')->put($finalPath, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        Storage::disk('local')->delete($tempPath);

        $document->update([
            'file_path' => $finalPath,
            'status' => PurchaseFileDocument::STATUS_COMPLETED,
            'error_message' => null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        $document = $this->document->fresh();

        if (! $document || $document->isProcessed()) {
            return;
        }

        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->update([
            'status' => PurchaseFileDocument::STATUS_FAILED,
            'error_message' => $exception?->getMessage() ?? 'Processing failed.',
        ]);
    }
}
