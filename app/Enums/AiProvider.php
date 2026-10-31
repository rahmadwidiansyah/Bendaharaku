<?php

declare(strict_types=1);

namespace App\Enums;

enum AiProvider: string
{
    case Gemini = 'gemini';
    case OpenAI = 'openai';
    case DeepSeek = 'deepseek';
    case OpenAiCompatible = 'openai-compatible';

    public function defaultModel(): string
    {
        return match ($this) {
            self::Gemini => 'gemini-2.0-flash',
            self::OpenAI => 'gpt-4o-mini',
            self::DeepSeek => 'deepseek-chat',
            self::OpenAiCompatible => (string) config('bendaharaku.ai.openai_compatible.model'),
        };
    }

    /**
     * Dukungan tool-calling tingkat provider.
     *
     * Semua provider yang didukung saat ini bisa mengirim tools. Khusus
     * openai-compatible, akun dapat menonaktifkannya via metadata.disable_tools
     * (lihat ModelResolver::resolve(..., requireTools: true)).
     */
    public function supportsTools(): bool
    {
        return match ($this) {
            self::Gemini, self::OpenAI, self::DeepSeek, self::OpenAiCompatible => true,
        };
    }
}
