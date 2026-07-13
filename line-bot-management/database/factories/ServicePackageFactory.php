<?php

namespace Database\Factories;

use App\Models\PackageCategory;
use App\Models\ServicePackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePackage>
 */
class ServicePackageFactory extends Factory
{
    protected $model = ServicePackage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->numberBetween(500, 30000);

        return [
            'category_id' => PackageCategory::factory(),
            'code' => strtoupper(fake()->bothify('PKG-####')),
            'name_th' => 'แพ็กเกจ'.fake()->word(),
            'name_en' => 'Package '.fake()->word(),
            'description_th' => fake()->optional()->sentence(10),
            'description_en' => fake()->optional()->sentence(10),
            'price' => $price,
            'sale_price' => fake()->optional()->numberBetween(300, $price),
            'currency' => 'THB',
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90, 120]),
            'terms' => fake()->optional()->sentence(),
            'keywords' => fake()->optional()->words(4, true),
            'is_active' => true,
            'is_published' => true,
            'effective_from' => null,
            'effective_until' => null,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
