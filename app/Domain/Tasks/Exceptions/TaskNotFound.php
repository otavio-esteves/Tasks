<?php

namespace App\Domain\Tasks\Exceptions;

use DomainException;

class TaskNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('Tarefa nao encontrada para esta equipe.');
    }
}
