<?php

namespace Database\Factories;

use App\Models\AuthenticationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuthenticationLog>
 */
class AuthenticationLogFactory extends Factory
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
            'provider' => 'local',
            'event' => 'login',
            'success' => true,
            'failure_reason' => null,
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => fake()->userAgent(),
            'session_id_hash' => hash('sha256', Str::random(40)),
            'request_id' => (string) Str::uuid(),
            'occurred_at' => now(),
            'metadata' => null,
        ];
    }
}
