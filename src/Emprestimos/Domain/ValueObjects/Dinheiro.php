<?php

namespace Emprestimos\Domain\ValueObjects;

use App\ValueObjects\AbstractValue;
use App\ValueObjects\ValueObject;
use InvalidArgumentException;

final class Dinheiro extends AbstractValue
{
    private function __construct(
        private readonly float $valor,
        private readonly string $moeda = 'BRL',
    ) {
        if ($valor < 0) {
            throw new InvalidArgumentException('O valor não pode ser negativo.');
        }
    }

    public static function fromNative($valor, string $moeda = 'BRL'): self
    {
        return new self((float) $valor, $moeda);
    }

    public function getNativeValue(): float
    {
        return $this->valor;
    }

    public function somar(self $outroValor): self
    {
        $this->mesmaMoeda($outroValor);

        return new self($this->valor + $outroValor->valor, $this->moeda);
    }

    public function multiplicar(int $fator): self
    {
        return new self($this->valor * $fator, $this->moeda);
    }

    public function igual(ValueObject $objeto): bool
    {
        return $objeto instanceof Dinheiro
            && $this->valor === $objeto->valor
            && $this->moeda === $objeto->moeda;
    }

    public function mesmaMoeda(self $outroValor): void
    {
        if ($this->moeda !== $outroValor->moeda) {
            throw new InvalidArgumentException('Não é possível realizar operações com moedas diferentes.');
        }
    }

    public function __toString(): string
    {
        $valor = number_format($this->valor, 2, ',', '.');

        return sprintf('%s %s', $valor, $this->moeda);
    }
}
