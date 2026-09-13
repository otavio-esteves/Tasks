<?php

namespace App\Domain\System\Exceptions;

use DomainException;

class InvalidPublicAccessUser extends DomainException
{
    public function __construct()
    {
        parent::__construct('Selecione um usuário comum, verificado e vinculado a uma equipe para o acesso sem login.');
    }
}
