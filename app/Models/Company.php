<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class Company extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'owner_name',
        'logo_path',
    ];

    protected $appends = [
        'logo_url',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Company $company) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
        });
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (blank($this->logo_path) || ! $this->exists) {
            return null;
        }

        return route('companies.logo.show', $this);
    }

    public function storeLogo(UploadedFile $file): string
    {
        $directory = 'company/'.$this->id;

        Storage::disk('public')->makeDirectory($directory);

        if ($this->logo_path) {
            Storage::disk('public')->delete($this->logo_path);
        }

        $path = Storage::disk('public')->putFile($directory, $file);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Unable to store company logo on disk.');
        }

        if (! Storage::disk('public')->exists($path)) {
            throw new RuntimeException('Company logo file was not written to storage.');
        }

        $this->forceFill(['logo_path' => $path])->save();

        return $path;
    }

    public function deleteLogo(): void
    {
        if ($this->logo_path) {
            Storage::disk('public')->delete($this->logo_path);
            $this->forceFill(['logo_path' => null])->save();
        }
    }

    public static function forReports(): ?self
    {
        return static::query()->orderBy('id')->first();
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function pdfHeaderNameLines(): array
    {
        $name = trim((string) $this->name);
        if ($name === '') {
            return ['', ''];
        }

        $words = preg_split('/\s+/', $name) ?: [];
        if (count($words) >= 3) {
            $secondary = array_splice($words, -2);

            return [strtoupper(implode(' ', $words)), implode(' ', $secondary)];
        }

        return [strtoupper($name), ''];
    }

    public function pdfLogoInitials(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $initials = '';
        foreach ($words as $word) {
            if ($word !== '') {
                $initials .= strtoupper($word[0]);
            }
        }

        return $initials;
    }

    public function pdfLogoDataUri(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        $path = Storage::disk('public')->path($this->logo_path);
        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
