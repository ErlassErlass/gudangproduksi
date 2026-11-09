<?php

namespace App\Domains\MasterData\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Letter / document template (agent_docs/05_DATABASE_SCHEMA.md §10).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $document_type
 * @property string $template_body
 * @property int $version
 * @property bool $is_active
 */
class DocumentTemplate extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
