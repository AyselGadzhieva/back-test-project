<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'is_shared',
        'is_protected',
        'path',
        'document_name',
        'owner_id',
        'folder_id',
        'download_link',
        'document_type_id',
        'size'
    ];

    protected $hidden = [
        'path',
        'download_link'
    ];

    protected $dates = ['deleted_at'];

    protected $casts = [
        'updated_at' => 'datetime:d-m-Y H:m:s',
        'created_at' => 'datetime:d-m-Y H:m:s',
        'is_shared' => 'boolean',
        'is_protected' => 'boolean',
    ];

    protected $guarded = [];

//    public $appends = ['document_type'];
//
//    public function getDocumentTypeAttribute()
//    {
//        return $this->documentType();
//    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function documentOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function usingDocument(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_permissions', 'document_id','user_id');
    }
}
