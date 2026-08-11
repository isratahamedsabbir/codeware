<?php

namespace Database\Factories;

use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_id'               => Job::factory()->open(),
            'name'                 => fake()->name(),
            'email'                => fake()->safeEmail(),
            'phone'                => '+880' . fake()->numerify('17########'),
            'resume_path'          => 'resumes/1/fake_resume.pdf',
            'resume_original_name' => 'resume.pdf',
            'status'               => 'pending',
        ];
    }

    public function reviewed(): static
    {
        return $this->state(['status' => 'reviewed']);
    }

    public function shortlisted(): static
    {
        return $this->state(['status' => 'shortlisted']);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected']);
    }
}
