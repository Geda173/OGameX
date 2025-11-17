<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property array $ships
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder|StandardFleet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StandardFleet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StandardFleet query()
 * @method static \Illuminate\Database\Eloquent\Builder|StandardFleet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StandardFleet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StandardFleet whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StandardFleet whereShips($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StandardFleet whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StandardFleet whereUserId($value)
 * @mixin \Eloquent
 */
class StandardFleet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'ships',
    ];

    protected $casts = [
        'ships' => 'array',
    ];

    /**
     * Get the user that owns the standard fleet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
