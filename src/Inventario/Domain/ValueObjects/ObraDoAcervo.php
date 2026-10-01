<?php

namespace Inventario\Domain\ValueObjects;

final readonly class ObraDoAcervo
{
    /**
     * @param  list<string>  $autores
     */
    public function __construct(
        public int $id,
        public string $titulo,
        public array $autores = [],
        public ?string $editora = null,
        public ?string $categoria = null,
        public ?int $anoPublicacao = null,
        public ?string $isbn = null,
        public ?string $capaUrl = null,
        public ?string $capaMiniaturaUrl = null,
        public int $exemplaresDisponiveis = 0,
        public int $exemplaresTotal = 0,
    ) {}

    public function temExemplarDisponivel(): bool
    {
        return $this->exemplaresDisponiveis > 0;
    }

    public function autoresFormatados(): string
    {
        return implode(', ', $this->autores);
    }

    public function igual(self $outra): bool
    {
        return $this->id === $outra->id;
    }

    public function __toString(): string
    {
        return $this->titulo;
    }
}
