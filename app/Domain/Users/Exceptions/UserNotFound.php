<?php

namespace App\Domain\Users\Exceptions;

use DomainException;

class UserNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('Usuário não encontrado.');
    }
}
