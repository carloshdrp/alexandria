<?php

namespace App\Concerns;

use DomainException;
use Throwable;

trait ExibeExcecaoDeDominio
{
    public function exception(Throwable $e, callable $stopPropagation): void
    {
        if (! $e instanceof DomainException) {
            return;
        }

        $this->addError('dominio', $e->getMessage());
        $stopPropagation();
    }
}
