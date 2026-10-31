<?php

namespace App\Exceptions;

use DomainException;

/** Paket Data Posko terkunci: kata sandi posko belum diketik / salah. */
class KataSandiPaketException extends DomainException
{
    public function __construct(string $message, public readonly bool $perluKataSandi = true)
    {
        parent::__construct($message);
    }
}
