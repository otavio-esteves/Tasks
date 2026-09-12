<?php

namespace App\Domain\Categories\Exceptions;

use DomainException;

class CategoryHasActiveTasks extends DomainException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function preventsMoving(): self
    {
        return new self('A categoria possui tarefas ativas e nao pode ser movida para outra equipe.');
    }

    public static function preventsDeletion(): self
    {
        return new self('A categoria possui tarefas ativas e nao pode ser excluida.');
    }
}
