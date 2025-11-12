<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentRecord>
 */
class StudentRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['pelanggaran', 'prestasi', 'perizinan', 'catatan_bk']);
        $points = 0;

        if ($type === 'pelanggaran') {
            $points = $this->faker->randomElement([-5, -10, -2, -15]);
        } elseif ($type === 'prestasi') {
            $points = $this->faker->randomElement([2, 5, 10]);
        }
        
        return [
            // foundation_id, school_id, student_id, reported_by_user_id
            // akan kita tentukan saat memanggil factory di Seeder
            
            'date' => $this->faker->dateTimeThisYear(),
            'type' => $type,
            'description' => $this->faker->sentence(),
            'points' => $points,
        ];
    }
}