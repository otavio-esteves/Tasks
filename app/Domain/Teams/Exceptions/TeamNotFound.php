<?php

namespace App\Domain\Teams\Exceptions;

use RuntimeException;

class TeamNotFound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Equipe nao encontrada.');
    }
}
