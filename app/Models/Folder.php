<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'is_shared',
        'is_protected',
        'parent_id',
        'owner_id'
    ];

    protected $casts = [
        'updated_at' => 'datetime:d-m-Y H:m:s',
        'created_at' => 'datetime:d-m-Y H:m:s',
        'is_shared' => 'boolean',
        'is_protected' => 'boolean',
    ];

    protected $hidden = ['parent_id', 'owner_id', 'deleted_at', 'created_at', 'updated_at'];

    public function parentFolder(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function childrenFolders(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function childrenFoldersRecursive(): HasMany
    {
        return $this->childrenFolders()->with('childrenFoldersRecursive');
    }

    public function documentsFolder(): HasMany
    {
        return $this->hasMany(Document::class, 'folder_id', 'id');
    }

    public function folderOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function usingFolders(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'folder_permissions', 'folder_id','user_id');
    }
}
