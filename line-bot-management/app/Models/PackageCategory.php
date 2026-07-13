<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PackageCategory extends Model
{
    /** @use HasFactory<\Database\Factories\PackageCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name_th',
        'name_en',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Auto-fill slug from the Thai (or English) name when left blank.
        static::saving(function (PackageCategory $category) {
            if (blank($category->slug)) {
                $base = Str::slug($category->name_en ?: $category->name_th);
                $category->slug = $base ?: 'category-'.uniqid();
            }
        });
    }

    /**
     * @return HasMany<ServicePackage, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(ServicePackage::class, 'category_id');
    }

    /**
     * @param  Builder<PackageCategory>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
