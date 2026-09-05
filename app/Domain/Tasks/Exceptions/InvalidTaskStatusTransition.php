<?php

namespace App\Domain\Tasks\Exceptions;

use App\Domain\Tasks\TaskStatus;
use DomainException;

class InvalidTaskStatusTransition extends DomainException
{
    public function __construct(TaskStatus $from, TaskStatus $to)
    {
        parent::__construct(sprintf(
            'Nao e permitido mudar o status da tarefa de "%s" para "%s".',
            $from->value,
            $to->value,
        ));
    }
}
