<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdmissionRegistration>
 */
class AdmissionRegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // foundation_id dan school_id akan diisi di Seeder
            'status' => $this->faker->randomElement(['baru', 'diverifikasi', 'diterima', 'ditolak']),
            'registration_wave' => $this->faker->randomElement(['Gelombang 1 2025', 'Gelombang 2 2025']),
            'registration_number' => 'PPDB-' . $this->faker->unique()->numberBetween(1000, 9999),
            
            'full_name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['L', 'P']),
            'birth_place' => $this->faker->city(),
            'birth_date' => $this->faker->date(),
            'religion' => 'Islam',
            'previous_school' => 'TK ' . $this->faker->company(),

            'parent_name' => $this->faker->name(),
            'parent_phone' => $this->faker->phoneNumber(),
            'parent_email' => $this->faker->safeEmail(),
            
            'payment_proof_path' => null,
        ];
    }
}