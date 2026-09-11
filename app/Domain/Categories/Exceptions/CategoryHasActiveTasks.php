<?php

namespace App\Domain\Categories\Exceptions;

use DomainException;

class CategoryHasActiveTasks extends DomainException
{
    public function __construct()
    {
        parent::__construct('A categoria possui tarefas ativas e nao pode ser movida para outra equipe.');
    }
}
