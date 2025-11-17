<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Space Dock repair queue model
 *
 * @property int $id
 * @property int $planet_id
 * @property int $battle_report_id
 * @property int $ship_object_id
 * @property int $ship_amount
 * @property int $ship_amount_claimed
 * @property int $metal_cost
 * @property int $crystal_cost
 * @property int $deuterium_cost
 * @property int $time_duration
 * @property int $time_start
 * @property int $time_end
 * @property int $processed
 * @property int $canceled
 * @property int $claimed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue query()
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereBattleReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereCanceled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereCrystalCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereDeuteriumCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereMetalCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue wherePlanetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereProcessed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereShipAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereShipAmountClaimed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereShipObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereTimeDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereTimeEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereTimeStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RepairQueue whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RepairQueue extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'repair_queue';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'planet_id',
        'battle_report_id',
        'ship_object_id',
        'ship_amount',
        'ship_amount_claimed',
        'metal_cost',
        'crystal_cost',
        'deuterium_cost',
        'time_duration',
        'time_start',
        'time_end',
        'processed',
        'canceled',
        'claimed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'planet_id' => 'integer',
        'battle_report_id' => 'integer',
        'ship_object_id' => 'integer',
        'ship_amount' => 'integer',
        'ship_amount_claimed' => 'integer',
        'metal_cost' => 'integer',
        'crystal_cost' => 'integer',
        'deuterium_cost' => 'integer',
        'time_duration' => 'integer',
        'time_start' => 'integer',
        'time_end' => 'integer',
        'processed' => 'integer',
        'canceled' => 'integer',
        'claimed' => 'integer',
    ];

    /**
     * Get the planet that owns this repair queue entry.
     */
    public function planet(): BelongsTo
    {
        return $this->belongsTo(Planet::class);
    }

    /**
     * Get the battle report that created this wreckage.
     */
    public function battleReport(): BelongsTo
    {
        return $this->belongsTo(BattleReport::class);
    }
}
