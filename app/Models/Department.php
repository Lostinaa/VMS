<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['name', 'site_id'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
