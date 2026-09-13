<?php

namespace App\Domain\Users\Exceptions;

use DomainException;

class PublicAccessUserCannotBeAdministrator extends DomainException
{
    public function __construct()
    {
        parent::__construct('A conta usada no acesso sem login deve permanecer como usuário comum.');
    }
}
