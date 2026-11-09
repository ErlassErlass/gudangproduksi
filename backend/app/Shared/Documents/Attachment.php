<?php

namespace App\Shared\Documents;

use App\Models\User;
use App\Shared\Exceptions\DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Private file attachment metadata. Internal storage paths are never exposed in API resources
 * (agent_docs/04_SYSTEM_ARCHITECTURE.md §8, 08_API_SPECIFICATION.md §5).
 *
 * @property int $id
 * @property string $owner_type
 * @property int $owner_id
 * @property string $file_name
 * @property string $disk
 * @property string $file_path
 * @property string $mime_type
 * @property int $file_size
 * @property int|null $uploaded_by
 */
class Attachment extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $hidden = ['file_path', 'disk'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public static function storeFor(string $ownerType, int $ownerId, UploadedFile $file, ?User $uploader): self
    {
        $allowedMimes = (array) config('pwms.attachments.allowed_mimes', []);
        $maxBytes = ((int) config('pwms.attachments.max_kb', 10_240)) * 1024;
        $mime = (string) $file->getMimeType();
        $size = (int) $file->getSize();

        if ($allowedMimes !== [] && ! in_array($mime, $allowedMimes, true)) {
            throw new DomainException("Tipe berkas [{$mime}] tidak diizinkan.", 'ATTACHMENT_INVALID_MIME', 422);
        }

        if ($size > $maxBytes) {
            throw new DomainException('Ukuran berkas melebihi batas maksimum.', 'ATTACHMENT_TOO_LARGE', 422);
        }

        $disk = (string) config('pwms.attachments.disk', 'local');
        $directory = 'attachments/'.$ownerType.'/'.now()->format('Y/m');
        $storedName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = Storage::disk($disk)->putFileAs($directory, $file, $storedName);

        return self::query()->create([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'file_name' => basename($file->getClientOriginalName()),
            'disk' => $disk,
            'file_path' => (string) $path,
            'mime_type' => $mime,
            'file_size' => $size,
            'uploaded_by' => $uploader?->getKey(),
        ]);
    }
}
