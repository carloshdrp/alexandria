<?php

namespace Emprestimos\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class ValorMulta
{
    const float VALOR_MULTA = 1.00;

    private function __construct(
        private int $diaAtraso,
        private Dinheiro $valorDia,
        private Dinheiro $total,
    ) {}

    public static function calcular(int $diaAtraso): self
    {
        if ($diaAtraso < 1) {
            throw new InvalidArgumentException('A multa deve ser gerada quando o empréstimo tem pelo menos um dia de atraso.');
        }

        $valorDia = Dinheiro::fromNative(self::VALOR_MULTA);
        $total = $valorDia->multiplicar($diaAtraso);

        return new self($diaAtraso, $valorDia, $total);
    }

    public function total(): Dinheiro
    {
        return $this->total;
    }

    public function valorDia(): Dinheiro
    {
        return $this->valorDia;
    }

    public function diasAtraso(): int
    {
        return $this->diaAtraso;
    }

    public function __toString(): string
    {
        return (string) $this->total;
    }
}
