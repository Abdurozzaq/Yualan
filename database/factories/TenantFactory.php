<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TenantFactory extends Factory
{
    /**
     * Nama model yang sesuai.
     *
     * @var string
     */
    protected $model = Tenant::class;

    /**
     * Definisikan state default dari model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->company(),
            'slug' => Str::slug($this->faker->unique()->company()),
            'invitation_code' => Str::upper(Str::random(8)),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'business_type' => $this->faker->word(),
            'is_active' => $this->faker->boolean(90),
            'ipaymu_api_key' => $this->faker->uuid(),
            'ipaymu_secret_key' => $this->faker->sha256(),
            'ipaymu_mode' => $this->faker->randomElement(['sandbox', 'production']),
            'pricing_plan_id' => $this->faker->uuid(),
            'subscription_ends_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'last_transaction_id' => $this->faker->uuid(),
            'is_subscribed' => $this->faker->boolean(),
            'owner_name' => $this->faker->name(),
            'owner_email' => $this->faker->unique()->safeEmail(),
            'subscription_status' => $this->faker->randomElement(['trial', 'active', 'inactive']),
        ];
    }
}
