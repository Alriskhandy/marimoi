<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserRoleAssignment>
 */
class UserRoleAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role_id' => Role::factory(),
            'opd_id' => null,
            'assigned_by' => User::factory(),
            'started_at' => now(),
            'ended_at' => null,
            'reason' => null,
        ];
    }
}
