<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_th' => fake()->sentence().'?',
            'answer_th' => fake()->paragraph(),
            'question_en' => fake()->optional()->sentence().'?',
            'answer_en' => fake()->optional()->paragraph(),
            'category' => fake()->randomElement(['ทั่วไป', 'สินค้าและบริการ', 'สถานที่', 'เงื่อนไข']),
            'tags' => fake()->optional()->words(3, true),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
