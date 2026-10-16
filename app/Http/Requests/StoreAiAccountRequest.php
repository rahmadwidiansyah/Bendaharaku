<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AiProvider;
use App\Services\AI\Security\SafeEndpointValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAiAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::enum(AiProvider::class)],
            'alias' => ['nullable', 'string', 'max:100'],
            'api_key' => ['required', 'string', 'min:8', 'max:500'],
            'base_url' => [
                'nullable', 'string', 'max:500', 'url',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }
                    // Validasi SSRF dipusatkan di SafeEndpointValidator (skema, DNS, IP privat/metadata).
                    if (($this->input('provider') ?? '') === AiProvider::OpenAiCompatible->value) {
                        try {
                            SafeEndpointValidator::normalizeBaseUrl($value);
                        } catch (\InvalidArgumentException $e) {
                            $fail($e->getMessage());
                        }
                    }
                },
            ],
            'priority' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }
}
