<?php

namespace Emprestimos\Domain\ValueObjects;

use Carbon\CarbonImmutable;
use DateTimeInterface;

final readonly class PrazoEmprestimo
{
    private const int DURACAO_DIAS = 14;

    private function __construct(
        private CarbonImmutable $retiradoEm,
        private CarbonImmutable $prazoDevolucao,
    ) {}

    public static function iniciar(DateTimeInterface $retiradoEm, int $duracaoDias = self::DURACAO_DIAS): self
    {
        $retirada = CarbonImmutable::instance($retiradoEm);

        return new self($retirada, $retirada->addDays($duracaoDias));
    }

    public static function reconstruir(DateTimeInterface $retiradoEm, DateTimeInterface $prazoDevolucao): self
    {
        return new self(CarbonImmutable::instance($retiradoEm), CarbonImmutable::instance($prazoDevolucao));
    }

    public function estaAtrasado(?DateTimeInterface $referencia = null): bool
    {
        $referencia = $referencia ? CarbonImmutable::instance($referencia) : CarbonImmutable::now();

        return $referencia->startOfDay()->greaterThan($this->prazoDevolucao->startOfDay());
    }

    public function diasAtraso(?DateTimeInterface $referencia = null): int
    {
        if (! $this->estaAtrasado($referencia)) {
            return 0;
        }

        $referencia = $referencia ? CarbonImmutable::instance($referencia) : CarbonImmutable::now();

        return (int) $this->prazoDevolucao->startOfDay()->diffInDays($referencia->startOfDay());
    }

    public function prazoDevolucao(): CarbonImmutable
    {
        return $this->prazoDevolucao;
    }

    public function retiradoEm(): CarbonImmutable
    {
        return $this->retiradoEm;
    }

    public function estender(int $duracaoDias = self::DURACAO_DIAS): self
    {
        return new self($this->retiradoEm, $this->prazoDevolucao->addDays($duracaoDias));
    }
}
