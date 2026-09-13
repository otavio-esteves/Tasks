<?php

namespace App\Domain\Users\Exceptions;

use DomainException;

class LastAdministratorRequired extends DomainException
{
    public function __construct()
    {
        parent::__construct('O sistema precisa manter pelo menos um administrador.');
    }
}
