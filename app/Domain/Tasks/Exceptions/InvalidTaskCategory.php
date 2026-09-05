<?php

namespace App\Domain\Tasks\Exceptions;

use DomainException;

class InvalidTaskCategory extends DomainException
{
    public function __construct()
    {
        parent::__construct('A categoria selecionada nao pertence a esta equipe.');
    }
}
