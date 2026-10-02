<?php

use Inventario\Domain\ValueObjects\CodigoPatrimonio;
use Inventario\Domain\ValueObjects\Isbn;

it('aceita ISBN válido e guarda só os dígitos', function (string $entrada, string $esperado) {
    expect(Isbn::fromNative($entrada)->getNativeValue())->toBe($esperado);
})->with([
    'ISBN-13 com hífens' => ['978-0-306-40615-7', '9780306406157'],
    'ISBN-10 com hífens' => ['0-306-40615-2', '0306406152'],
    'ISBN-10 terminado em X' => ['0-8044-2957-X', '080442957X'],
]);

it('recusa ISBN inválido', function (string $entrada) {
    expect(fn () => Isbn::fromNative($entrada))->toThrow(InvalidArgumentException::class);
})->with([
    'dígito verificador errado no ISBN-13' => '978-0-306-40615-8',
    'dígito verificador errado no ISBN-10' => '0-306-40615-3',
    'tamanho errado' => '12345',
    'sem dígitos' => 'ABC',
]);

it('normaliza o código de patrimônio', function () {
    expect(CodigoPatrimonio::fromNative('  ex-000123 ')->getNativeValue())->toBe('EX-000123');
});

it('recusa código de patrimônio fora do padrão', function (string $entrada) {
    expect(fn () => CodigoPatrimonio::fromNative($entrada))->toThrow(InvalidArgumentException::class);
})->with(['EX-123', 'AB-000123', '000123', 'EX-0001234']);
