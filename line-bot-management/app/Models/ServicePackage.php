<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ServicePackage extends Model
{
    /** @use HasFactory<\Database\Factories\ServicePackageFactory> */
    use HasFactory;

    protected $table = 'packages';

    protected $fillable = [
        'category_id',
        'code',
        'name_th',
        'name_en',
        'description_th',
        'description_en',
        'price',
        'sale_price',
        'currency',
        'duration_minutes',
        'terms',
        'keywords',
        'is_active',
        'is_published',
        'effective_from',
        'effective_until',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'duration_minutes' => 'integer',
            'is_active' => 'boolean',
            'is_published' => 'boolean',
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    /**
     * @return BelongsTo<PackageCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PackageCategory::class, 'category_id');
    }

    /**
     * @param  Builder<ServicePackage>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<ServicePackage>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Only packages inside their effective date window (per the shared product contract).
     *
     * @param  Builder<ServicePackage>  $query
     */
    public function scopeEffective(Builder $query, ?Carbon $on = null): void
    {
        $date = ($on ?? now())->toDateString();

        $query->where(function (Builder $q) use ($date) {
            $q->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date);
        })->where(function (Builder $q) use ($date) {
            $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date);
        });
    }
}
