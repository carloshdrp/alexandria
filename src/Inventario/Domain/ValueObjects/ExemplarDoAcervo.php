<?php

namespace Inventario\Domain\ValueObjects;

final readonly class ExemplarDoAcervo
{
    public function __construct(
        public int $id,
        public int $obraId,
        public string $codigoPatrimonio,
        public bool $disponivel,
        public bool $reservado,
        public string $tituloObra = '',
        public string $situacao = '',
        public string $estadoConservacao = '',
    ) {}

    public function igual(self $outro): bool
    {
        return $this->id === $outro->id;
    }

    public function __toString(): string
    {
        return $this->codigoPatrimonio;
    }
}
