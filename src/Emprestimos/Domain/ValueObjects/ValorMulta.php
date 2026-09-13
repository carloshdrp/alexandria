<?php

namespace Emprestimos\Domain\ValueObjects;

use App\ValueObjects\AbstractValue;
use InvalidArgumentException;

final class ValorMulta extends AbstractValue
{
    const float VALOR_MULTA = 1.00;

    public function __construct(
        private readonly int $diaAtraso,
        private readonly Dinheiro $valorDia,
        private readonly Dinheiro $total,
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
}
