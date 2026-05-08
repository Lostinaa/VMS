<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScreeningQuestion extends Model
{
    protected $fillable = [
        'question_text', 'question_text_am', 'type', 'options',
        'is_required', 'is_active', 'applies_to', 'flag_answer', 'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(ScreeningResponse::class);
    }

    /**
     * Get active questions applicable to a visitor type.
     */
    public static function forVisitorType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->where(function ($q) use ($type) {
                $q->where('applies_to', 'all')
                  ->orWhere('applies_to', $type);
            })
            ->orderBy('sort_order')
            ->get();
    }
}
