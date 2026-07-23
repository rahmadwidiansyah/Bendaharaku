<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletSide: string
{
    case Source = 'SOURCE';
    case Destination = 'DESTINATION';
    case None = 'NONE';
    case Both = 'BOTH';
}
