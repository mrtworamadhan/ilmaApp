<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Foundation; // <-- Import
use App\Models\School; // <-- Import

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Kita akan biarkan relasi diisi saat seeder
            // 'foundation_id' => Foundation::factory(),
            // 'school_id' => School::factory(),
            'user_id' => null,
            
            'nip' => $this->faker->unique()->numerify('##########'),
            'full_name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['L', 'P']),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'photo_path' => null,
            'birth_date' => $this->faker->date(),
            'employment_status' => $this->faker->randomElement(['PNS', 'GTY', 'Honorer']),
            'education_level' => $this->faker->randomElement(['S1', 'S2', 'D3']),
        ];
    }
}