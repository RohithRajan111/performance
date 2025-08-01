<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory; // Make sure to import the User model

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveApplication>
 */
class LeaveApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $endDate = (clone $startDate)->modify('+'.rand(0, 4).' days'); // Make it last 0-4 days
        $leaveDays = $startDate->diff($endDate)->days + 1;

        $dayType = $this->faker->randomElement(['full_day', 'half_day']);

        if ($dayType === 'half_day') {
            $endDate = $startDate; // Half-day leave is always a single day
            $leaveDays = 0.5;
        }

        return [
            // Associate with a random existing user
            'user_id' => User::inRandomOrder()->first()->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $this->faker->sentence(),
            'leave_type' => $this->faker->randomElement(['annual', 'sick', 'personal']),
            'day_type' => $dayType,
            'leave_days' => $leaveDays,
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'start_half_session' => $dayType === 'half_day' ? $this->faker->randomElement(['morning', 'afternoon']) : null,
            'end_half_session' => null, // Typically only set for multi-day half-day leaves, can be left null for simplicity
        ];
    }
}
