<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'customer_id' => $this->faker->uuid(),
            'account_type' => 'standard',
            'fname' => $this->faker->firstName,
            'sname' => $this->faker->lastName,
            'dob' => $this->faker->date('Y-m-d', '2000-01-01'),
            'gender' => $this->faker->randomElement(['M', 'F']),
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'), // or Hash::make('password')
            'timezone' => 'UTC',
            'current_timezone' => $this->faker->timezone,
//            'mfa_totp_secret' => Str::random(32),
//            'mfa_sms_phone' => $this->faker->phoneNumber,
//            'mfa_email' => $this->faker->safeEmail,
//            'mfa_default' => $this->faker->randomElement(['totp', 'sms', 'email']),
            'marketing_consent' => $this->faker->boolean(),
//            'full_access' => $this->faker->boolean(),
//            'personal_access' => $this->faker->boolean(),
//            'personal_plus_access' => $this->faker->boolean(),
//            'business_access' => $this->faker->boolean(),
//            'business_plus_access' => $this->faker->boolean(),
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null, // or $this->faker->optional()->dateTime
        ];
    }
}
