<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movie>
 */
class MovieFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tmdb_id' => (string) fake()->unique()->numberBetween(1000, 999999),
            'title' => fake()->sentence(3),
            'year' => fake()->numberBetween(1990, 2026),
            'type' => 'movie',
            'genre' => 'Drame',
            'director' => fake()->name(),
            'studio' => fake()->company(),
            'runtime' => '110 min',
            'tmdb_rating' => fake()->randomFloat(1, 4, 9),
        ];
    }
}
