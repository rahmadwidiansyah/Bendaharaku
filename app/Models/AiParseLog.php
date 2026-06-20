<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiParseLog extends Model
{
    // Tambahkan kolom schema baru ke fillable
    protected $fillable = [
        'user_id', 'transaction_log_id', 'provider', 'model', 'input_text', 
        'raw_response', 'raw_confidence', 'final_confidence', 'is_success', 
        'status', 'error_message', 'prompt_tokens', 'completion_tokens', 'total_tokens'
    ];

    protected function casts(): array {
        return [
            'raw_response' => 'array',
            'raw_confidence' => 'float',
            'final_confidence' => 'float',
            'is_success' => 'boolean',
        ];
    }
}