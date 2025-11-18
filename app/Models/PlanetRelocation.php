<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $planet_id
 * @property int $user_id
 * @property int $from_galaxy
 * @property int $from_system
 * @property int $from_position
 * @property int $to_galaxy
 * @property int $to_system
 * @property int $to_position
 * @property int $time_start
 * @property int $time_end
 * @property bool $processed
 * @property bool $cancelled
 * @property string|null $cancel_reason
 */
class PlanetRelocation extends Model
{
    protected $fillable = [
        'planet_id',
        'user_id',
        'from_galaxy',
        'from_system',
        'from_position',
        'to_galaxy',
        'to_system',
        'to_position',
        'time_start',
        'time_end',
        'processed',
        'cancelled',
        'cancel_reason',
    ];

    protected $casts = [
        'processed' => 'boolean',
        'cancelled' => 'boolean',
    ];

    public function planet(): BelongsTo
    {
        return $this->belongsTo(Planet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
