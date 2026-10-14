<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormImportPage extends Model
{
    protected $fillable = [
        'form_import_id',
        'page_number',
        'path',
        'width',
        'height',
    ];

    public function formImport(): BelongsTo
    {
        return $this->belongsTo(FormImport::class);
    }
}
