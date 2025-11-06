<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherAttendance>
 */
class TeacherAttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['H', 'S', 'I', 'A', 'DL']);
        $isHadir = $status === 'H' || $status === 'DL';
        
        return [
            // Relasi (foundation_id, school_id, teacher_id, reported_by_user_id)
            // akan kita tentukan saat memanggil factory di Seeder
            
            'date' => $this->faker->dateTimeThisMonth(),
            'status' => $status,
            'timestamp_in' => $isHadir ? $this->faker->time('07:i:s') : null,
            'timestamp_out' => $isHadir ? $this->faker->time('15:i:s') : null,
            'method' => $isHadir ? $this->faker->randomElement(['rfid', 'manual']) : 'manual',
            'notes' => !$isHadir ? $this->faker->sentence(3) : null,
        ];
    }
}