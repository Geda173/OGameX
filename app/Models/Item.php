<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use OGame\Models\Setting;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $icon
 * @property int $price
 * @property string $rarity
 * @property string $type
 * @property string|null $effect_type
 * @property int|null $effect_value
 * @property string $duration
 * @property string $category_id
 * @property string $ref_id
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @method static Builder|Item newModelQuery()
 * @method static Builder|Item newQuery()
 * @method static Builder|Item query()
 * @method static Builder|Item active()
 * @method static Builder|Item ofType(string $type)
 * @method static Builder|Item inCategory(string $categoryId)
 * @mixin \Eloquent
 */
class Item extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'icon',
        'price',
        'discount_percentage',
        'rarity',
        'type',
        'effect_type',
        'effect_value',
        'duration',
        'category_id',
        'ref_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'integer',
        'effect_value' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user items for this item.
     *
     * @return HasMany
     */
    public function userItems(): HasMany
    {
        return $this->hasMany(UserItem::class);
    }

    /**
     * Scope a query to only include active items.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include items of a given type.
     *
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include items in a given category.
     *
     * @param Builder $query
     * @param string $categoryId
     * @return Builder
     */
    public function scopeInCategory($query, string $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Get the effect duration in human readable format.
     *
     * @return string
     */
    public function getEffectDurationAttribute(): string
    {
        if (!$this->effect_value) {
            return '';
        }

        $hours = floor($this->effect_value / 3600);
        $minutes = floor(($this->effect_value % 3600) / 60);

        if ($hours > 0) {
            return $hours . 'h';
        }

        return $minutes . 'm';
    }

    /**
     * Get the formatted price for display.
     *
     * @return string
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->price >= 1000) {
            return number_format($this->price / 1000, 1) . 'K DM';
        }

        return number_format($this->price) . ' DM';
    }

    /**
     * Get the CSS class for rarity.
     *
     * @return string
     */
    public function getRarityClassAttribute(): string
    {
        return 'r_' . $this->rarity;
    }

    /**
     * Check if item has a discount.
     * Checks item-specific discount first, then server-wide shop discount.
     *
     * @return bool
     */
    public function hasDiscount(): bool
    {
        // Check item-specific discount first
        if ($this->discount_percentage > 0) {
            return true;
        }

        // Check server-wide shop discount
        $discountEnabled = Setting::where('key', 'shop_discount_enabled')->value('value');
        $discountPercentage = Setting::where('key', 'shop_discount_percentage')->value('value');

        return $discountEnabled == '1' && $discountPercentage > 0;
    }

    /**
     * Get the effective discount percentage.
     * Returns item-specific discount if set, otherwise server-wide discount.
     *
     * @return int
     */
    public function getEffectiveDiscountPercentage(): int
    {
        // Item-specific discount takes priority
        if ($this->discount_percentage > 0) {
            return $this->discount_percentage;
        }

        // Check server-wide discount
        $discountEnabled = Setting::where('key', 'shop_discount_enabled')->value('value');
        if ($discountEnabled != '1') {
            return 0;
        }

        $discountPercentage = Setting::where('key', 'shop_discount_percentage')->value('value');
        return (int) $discountPercentage;
    }

    /**
     * Get the discounted price.
     *
     * @return int
     */
    public function getDiscountedPrice(): int
    {
        $discountPercentage = $this->getEffectiveDiscountPercentage();

        if ($discountPercentage == 0) {
            return $this->price;
        }

        return (int) ($this->price * (1 - ($discountPercentage / 100)));
    }

    /**
     * Get the formatted discounted price for display.
     *
     * @return string
     */
    public function getFormattedDiscountedPriceAttribute(): string
    {
        $price = $this->getDiscountedPrice();

        if ($price >= 1000) {
            return number_format($price / 1000, 1) . 'K DM';
        }

        return number_format($price) . ' DM';
    }
}
