<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentAttendance>
 */
class StudentAttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Relasi (foundation_id, school_id, class_id, student_id, reported_by_user_id)
            // akan kita tentukan saat memanggil factory di Seeder
            
            'date' => $this->faker->dateTimeThisMonth(),
            'status' => $this->faker->randomElement(['H', 'S', 'I', 'A']),
            'notes' => $this->faker->boolean(20) ? $this->faker->sentence(3) : null, // 20% kemungkinan ada catatan
        ];
    }
}