<?php

namespace Emprestimos\Domain\ValueObjects;

use Carbon\CarbonImmutable;
use DateTimeInterface;

final readonly class PrazoEmprestimo
{
    private const int DURACAO_DIAS = 14;

    private const int JANELA_RENOVACAO_DIAS = 1;

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

    public function diasAtraso(?DateTimeInterface $referencia = null): int
    {
        if (! $this->estaAtrasado($referencia)) {
            return 0;
        }

        $referencia = $referencia ? CarbonImmutable::instance($referencia) : CarbonImmutable::now();

        return (int) $this->prazoDevolucao->startOfDay()->diffInDays($referencia->startOfDay());
    }

    public function estaAtrasado(?DateTimeInterface $referencia = null): bool
    {
        $referencia = $referencia ? CarbonImmutable::instance($referencia) : CarbonImmutable::now();

        return $referencia->startOfDay()->greaterThan($this->prazoDevolucao->startOfDay());
    }

    public function estaNaJanelaDeRenovacao(?DateTimeInterface $referencia = null): bool
    {
        return ! $this->estaAtrasado() && $this->diasParaDevolucao($referencia) <= self::JANELA_RENOVACAO_DIAS;
    }

    public function diasParaDevolucao(?DateTimeInterface $referencia = null): int
    {
        $referencia = $referencia ? CarbonImmutable::instance($referencia) : CarbonImmutable::now();

        return (int) $referencia->startOfDay()->diffInDays($this->prazoDevolucao->startOfDay());
    }

    public function inicioDaJanelaDeRenovacao(): CarbonImmutable
    {
        return $this->prazoDevolucao->subDays(self::JANELA_RENOVACAO_DIAS);
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

    public function igual(self $outro): bool
    {
        return $this->retiradoEm->equalTo($outro->retiradoEm)
            && $this->prazoDevolucao->equalTo($outro->prazoDevolucao);
    }
}
