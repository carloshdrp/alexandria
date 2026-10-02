<?php

use Emprestimos\Domain\ValueObjects\Dinheiro;
use Emprestimos\Domain\ValueObjects\ValorMulta;

it('cobra um real por dia de atraso', function (int $dias, float $total) {
    $multa = ValorMulta::calcular($dias);

    expect($multa->total()->getNativeValue())->toBe($total)
        ->and($multa->valorDia()->getNativeValue())->toBe(1.0)
        ->and($multa->diasAtraso())->toBe($dias);
})->with([
    'um dia' => [1, 1.0],
    'uma semana' => [7, 7.0],
    'um mês' => [30, 30.0],
]);

it('não gera multa sem pelo menos um dia de atraso', function () {
    expect(fn () => ValorMulta::calcular(0))->toThrow(InvalidArgumentException::class);
});

it('apresenta o total em reais', function () {
    expect((string) ValorMulta::calcular(3))->toBe('3,00 BRL');
});

it('não aceita valor negativo', function () {
    expect(fn () => Dinheiro::fromNative(-0.01))->toThrow(InvalidArgumentException::class);
});

it('não soma moedas diferentes', function () {
    expect(fn () => Dinheiro::fromNative(1)->somar(Dinheiro::fromNative(1, 'USD')))
        ->toThrow(InvalidArgumentException::class);
});

it('compara valor e moeda', function () {
    expect(Dinheiro::fromNative(2)->igual(Dinheiro::fromNative(2.0)))->toBeTrue()
        ->and(Dinheiro::fromNative(2)->igual(Dinheiro::fromNative(2, 'USD')))->toBeFalse()
        ->and(Dinheiro::fromNative(1)->somar(Dinheiro::fromNative(1))->igual(Dinheiro::fromNative(2)))->toBeTrue()
        ->and(Dinheiro::fromNative(1.5)->multiplicar(3)->igual(Dinheiro::fromNative(4.5)))->toBeTrue();
});
