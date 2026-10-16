<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\AI\Security\SafeEndpointValidator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAiAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'alias' => ['sometimes', 'string', 'max:100'],
            'api_key' => ['sometimes', 'nullable', 'string', 'min:8', 'max:500'],
            'base_url' => [
                'sometimes', 'nullable', 'string', 'max:500', 'url',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }
                    try {
                        SafeEndpointValidator::normalizeBaseUrl($value);
                    } catch (\InvalidArgumentException $e) {
                        $fail($e->getMessage());
                    }
                },
            ],
            'priority' => ['sometimes', 'integer', 'min:1', 'max:99'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
