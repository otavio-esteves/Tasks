<?php

namespace App\Domain\Users\Exceptions;

use DomainException;

class UserEmailAlreadyExists extends DomainException
{
    public function __construct()
    {
        parent::__construct('Já existe um usuário cadastrado com este e-mail.');
    }
}
