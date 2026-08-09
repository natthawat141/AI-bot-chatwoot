<?php

namespace Database\Factories;

use App\Models\PackageCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PackageCategory>
 */
class PackageCategoryFactory extends Factory
{
    protected $model = PackageCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nameEn = fake()->unique()->words(2, true);

        return [
            'name_th' => 'หมวด'.fake()->word(),
            'name_en' => Str::title($nameEn),
            'slug' => Str::slug($nameEn),
            'description' => fake()->optional()->sentence(),
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
