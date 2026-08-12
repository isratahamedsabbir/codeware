<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(3, '_'),
            'name' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'subject_template' => fake()->sentence(),
            'body_template' => '<p>'.fake()->paragraph().'</p>',
            'variables' => ['customer_name', 'order_id'],
            'active' => true,
        ];
    }
}
