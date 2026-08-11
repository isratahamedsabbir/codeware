<?php

namespace Database\Factories;

use App\Models\MediaLibrary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaLibrary>
 */
class MediaLibraryFactory extends Factory
{
    public function definition(): array
    {
        $filename = fake()->uuid().'.jpg';
        $path = 'media/'.$filename;

        return [
            'uuid'              => (string) Str::uuid(),
            'filename'          => $filename,
            'original_filename' => fake()->word().'.jpg',
            'mime_type'         => 'image/jpeg',
            'file_type'         => 'image',
            'file_size'         => fake()->numberBetween(10000, 5000000),
            'disk'              => 'public',
            'path'              => $path,
            'url'               => 'http://localhost/storage/'.$path,
            'title'             => null,
            'alt_text'          => fake()->sentence(3),
            'caption'           => null,
            'description'       => null,
            'metadata'          => ['width' => 800, 'height' => 600],
            'uploaded_by'       => User::factory(),
        ];
    }
}
