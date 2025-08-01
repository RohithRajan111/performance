<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->words(3, true)),
            'project_id' => Project::inRandomOrder()->first()->id,
            'assigned_to_id' => User::inRandomOrder()->first()->id,
            'status' => $this->faker->randomElement(['todo', 'in_progress', 'completed']),
            'due_date' => $this->faker->dateTimeBetween('now', '+2 months'),
        ];
    }
}
