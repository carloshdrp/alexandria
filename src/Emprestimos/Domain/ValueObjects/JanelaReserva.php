<?php

namespace Emprestimos\Domain\ValueObjects;

use Carbon\CarbonImmutable;
use DateTimeInterface;

final readonly class JanelaReserva
{
    private const int DURACAO_HORAS = 48;

    public function __construct(
        private CarbonImmutable $disponibilizadaEm,
        private CarbonImmutable $expiraEm,
    ) {}

    public static function abrir(DateTimeInterface $disponibilizadaEm): self
    {
        $inicio = CarbonImmutable::instance($disponibilizadaEm);

        $fim = $inicio->addHours(self::DURACAO_HORAS);

        return new self($inicio, $fim);
    }

    public static function reconstruir(DateTimeInterface $disponibilizadaEm, DateTimeInterface $expiraEm): self
    {
        return new self(CarbonImmutable::instance($disponibilizadaEm), CarbonImmutable::instance($expiraEm));
    }

    public function expirou(?DateTimeInterface $referencia = null): bool
    {
        $referencia = $referencia ? CarbonImmutable::instance($referencia) : CarbonImmutable::now();

        return $referencia->greaterThan($this->expiraEm);
    }

    public function disponibilizadaEm(): CarbonImmutable
    {
        return $this->disponibilizadaEm;
    }

    public function expiraEm(): CarbonImmutable
    {
        return $this->expiraEm;
    }
}
