<?php

namespace App\Domain\Users\Exceptions;

use DomainException;

class InvalidUserTeam extends DomainException
{
    public function __construct()
    {
        parent::__construct('Usuários comuns precisam estar vinculados a uma equipe ativa.');
    }
}
