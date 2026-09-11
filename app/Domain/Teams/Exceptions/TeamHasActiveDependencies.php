<?php

namespace App\Domain\Teams\Exceptions;

use DomainException;

class TeamHasActiveDependencies extends DomainException
{
    public function __construct()
    {
        parent::__construct('A equipe possui usuarios, categorias ou tarefas ativas e nao pode ser removida.');
    }
}
