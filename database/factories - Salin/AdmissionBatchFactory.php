<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdmissionBatch>
 */
class AdmissionBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeThisMonth();
        
        return [
            // foundation_id dan school_id akan diisi di Seeder
            'name' => 'Gelombang ' . $this->faker->randomElement(['1', '2']) . ' ' . date('Y'),
            'description' => $this->faker->paragraph(),
            'start_date' => $startDate,
            'end_date' => $this->faker->dateTimeBetween($startDate, '+30 days'),
            'fee_amount' => $this->faker->randomElement([150000, 200000, 250000]),
            'is_active' => $this->faker->boolean(70), // 70% kemungkinan true
        ];
    }
}