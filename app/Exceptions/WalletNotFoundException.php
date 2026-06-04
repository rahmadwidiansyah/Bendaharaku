<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class WalletNotFoundException extends Exception
{
    public function __construct(string $message = "Resolusi gagal: Dompet tidak ditemukan atau tidak dikenali.", int $code = 404)
    {
        parent::__construct($message, $code);
    }
}