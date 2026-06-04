<?php

declare(strict_types=1);

namespace App\Enums;

enum AiProvider: string
{
    case Gemini = 'gemini';
    case OpenAI = 'openai';
    case DeepSeek = 'deepseek';

    public function defaultModel(): string
    {
        return match ($this) {
            self::Gemini => 'gemini-2.5-flash',
            self::OpenAI => 'gpt-4o-mini',
            self::DeepSeek => 'deepseek-chat',
        };
    }
}
