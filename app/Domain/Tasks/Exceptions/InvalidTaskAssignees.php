<?php

namespace App\Domain\Tasks\Exceptions;

use DomainException;

class InvalidTaskAssignees extends DomainException
{
    public function __construct()
    {
        parent::__construct('Todos os responsáveis pela tarefa devem pertencer à equipe selecionada.');
    }
}
