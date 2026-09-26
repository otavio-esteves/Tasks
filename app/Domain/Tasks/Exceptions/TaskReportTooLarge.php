<?php

namespace App\Domain\Tasks\Exceptions;

use DomainException;

class TaskReportTooLarge extends DomainException
{
    public const MAX_TASKS = 500;

    public function __construct()
    {
        parent::__construct('O relatório contém mais de '.self::MAX_TASKS.' tarefas. Restrinja os filtros para imprimir.');
    }
}
