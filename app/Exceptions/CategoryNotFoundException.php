<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class CategoryNotFoundException extends Exception
{
    public function __construct(string $message = 'Resolusi gagal: Kategori tidak ditemukan atau tidak dikenali.', int $code = 404)
    {
        parent::__construct($message, $code);
    }
}
