<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiParseLog extends Model
{
    protected $fillable = [
        'user_id', 'provider', 'model', 'input_text', 
        'raw_response', 'confidence', 'is_success', 'error_message'
    ];

    protected function casts(): array {
        return [
            'raw_response' => 'array',
            'confidence' => 'float',
            'is_success' => 'boolean',
        ];
    }
}