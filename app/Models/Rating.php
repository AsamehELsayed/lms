<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Rating extends Model
{
    protected $fillable = [
        'user_id',
        'rating',
        'review',
        'rateable_id',
        'rateable_type',
    ];

    /**
     * Get the model (course/instructor/etc.) that this rating belongs to.
     */
    public function rateable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who wrote the rating.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
