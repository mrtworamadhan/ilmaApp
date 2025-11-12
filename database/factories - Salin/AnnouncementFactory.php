<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // foundation_id, school_id, dan user_id
            // akan kita tentukan saat memanggil factory di Seeder

            'title' => $this->faker->sentence(5),
            'content' => $this->faker->paragraphs(3, true), // 3 paragraf
            'target_roles' => $this->faker->randomElement([null, ['Wali Murid'], ['Guru', 'Wali Murid']]),
            'status' => $this->faker->randomElement(['draft', 'published']),
            'published_at' => $this->faker->dateTimeThisMonth(),
        ];
    }
}