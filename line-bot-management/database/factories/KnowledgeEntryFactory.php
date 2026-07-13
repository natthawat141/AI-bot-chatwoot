<?php

namespace Database\Factories;

use App\Models\KnowledgeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeEntry>
 */
class KnowledgeEntryFactory extends Factory
{
    protected $model = KnowledgeEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'body' => fake()->paragraphs(3, true),
            'type' => fake()->randomElement(['general', 'policy', 'promotion', 'procedure']),
            'category' => fake()->optional()->word(),
            'tags' => fake()->optional()->words(3, true),
            'source_url' => fake()->optional()->url(),
            'version' => 1,
            'is_active' => true,
            'reviewed_at' => fake()->optional()->dateTimeThisYear(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
