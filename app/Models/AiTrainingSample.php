<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiTrainingSample extends Model
{
    protected $fillable = [
        'hash_signature', 'source', 'input_text', 'provider', 'model', // provider, model ditambahkan
        'confidence', 'original_prediction', 'expected_json',          // confidence, original_prediction ditambahkan
        'quality_score', 'status', 'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'expected_json' => 'array',
            'quality_score' => 'float',
            'is_verified' => 'boolean',
        ];
    }
}