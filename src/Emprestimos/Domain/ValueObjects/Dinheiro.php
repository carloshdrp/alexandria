<?php

namespace Emprestimos\Domain\ValueObjects;

use App\Casts\ValueObjectCast;
use App\ValueObjects\ValueObject;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;
use JsonSerializable;

final class Dinheiro implements Castable, JsonSerializable, ValueObject
{
    private function __construct(
        private readonly float $valor,
        private readonly string $moeda = 'BRL',
    ) {
        if ($valor < 0) {
            throw new InvalidArgumentException('O valor não pode ser negativo.');
        }
    }

    public static function fromNative(mixed $valor, string $moeda = 'BRL'): static
    {
        return new self((float) $valor, $moeda);
    }

    /**
     * @param  array<int, string>  $arguments
     * @return CastsAttributes<ValueObject, ValueObject>
     */
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new ValueObjectCast(self::class, ...$arguments);
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

    public function mesmaMoeda(self $outroValor): void
    {
        if ($this->moeda !== $outroValor->moeda) {
            throw new InvalidArgumentException('Não é possível realizar operações com moedas diferentes.');
        }
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

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }

    public function __toString(): string
    {
        $valor = number_format($this->valor, 2, ',', '.');

        return sprintf('%s %s', $valor, $this->moeda);
    }
}
