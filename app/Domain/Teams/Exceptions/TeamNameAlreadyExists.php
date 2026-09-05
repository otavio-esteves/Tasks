<?php

namespace App\Domain\Teams\Exceptions;

use RuntimeException;

class TeamNameAlreadyExists extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ja existe uma equipe com este nome.');
    }
}
