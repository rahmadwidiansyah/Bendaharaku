<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionSource: string
{
    case TELEGRAM = 'telegram';
    case WEB_CHAT = 'web_chat';
    case WEB = 'web';
    case OCR = 'ocr';
    case DRAFT = 'draft';
    case IMPORT = 'import';
    case API = 'api';
    case RECURRING = 'recurring';
    case SYSTEM = 'system';
}
