<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numberBetween(1, 999999),
            'name' => ucfirst(fake()->words(3, true)),
            'description' => fake()->sentence(12),
            'image_url' => 'https://picsum.photos/seed/'.fake()->unique()->uuid().'/600/600',
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the product is hidden from the public.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
