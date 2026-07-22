<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EvidenceStatus;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Evidence>
 */
class EvidenceFactory extends Factory
{
    protected $model = Evidence::class;

    public function definition(): array
    {
        $originalName = fake()->uuid().'.jpg';

        return [
            'uuid' => Str::uuid(),
            'user_id' => User::factory(),
            'original_name' => $originalName,
            'stored_name' => fake()->uuid().'.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => fake()->numberBetween(100000, 5000000),
            'disk' => 'evidence',
            'path' => fake()->uuid().'/'.$originalName,
            'status' => EvidenceStatus::Uploaded,
            'source' => 'CHAT_UPLOAD',
            'retry_count' => 0,
        ];
    }

    public function uploaded(): static
    {
        return $this->state(fn () => ['status' => EvidenceStatus::Uploaded]);
    }

    public function queued(): static
    {
        return $this->state(fn () => ['status' => EvidenceStatus::Queued]);
    }

    public function processing(): static
    {
        return $this->state(fn () => ['status' => EvidenceStatus::Processing]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => EvidenceStatus::Completed]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => EvidenceStatus::Failed]);
    }
}
