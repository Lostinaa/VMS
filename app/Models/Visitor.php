<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Visitor extends Model
{
    use Notifiable;

    protected $fillable = [
        'full_name', 'email', 'phone', 'organization',
        'id_type', 'id_number', 'photo', 'car_plate_number', 'id_photo_path',
        'is_blacklisted', 'blacklist_reason',
        'is_whitelisted', 'whitelist_expires_at', 'whitelist_reason',
    ];

    protected $casts = [
        'is_blacklisted' => 'boolean',
        'is_whitelisted' => 'boolean',
        'whitelist_expires_at' => 'date',
    ];

    public function visitRequests(): HasMany
    {
        return $this->hasMany(VisitRequest::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function blacklists(): HasMany
    {
        return $this->hasMany(Blacklist::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
