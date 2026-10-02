<?php

use App\Acesso\Domain\ValueObjects\Documento;
use App\Acesso\Domain\ValueObjects\Telefone;

it('guarda o CPF como dígitos e formata de volta', function () {
    $documento = Documento::fromNative('529.982.247-25');

    expect($documento->getNativeValue())->toBe('52998224725')
        ->and($documento->formatado())->toBe('529.982.247-25')
        ->and($documento->igual(Documento::fromNative('52998224725')))->toBeTrue();
});

it('recusa CPF sem onze dígitos', function (string $entrada) {
    expect(fn () => Documento::fromNative($entrada))->toThrow(InvalidArgumentException::class);
})->with(['1234567890', '123456789012', '']);

it('guarda o telefone como dígitos com DDD', function (string $entrada, string $esperado) {
    expect(Telefone::fromNative($entrada)->getNativeValue())->toBe($esperado);
})->with([
    'celular' => ['(11) 98888-7777', '11988887777'],
    'fixo' => ['(11) 3888-7777', '1138887777'],
]);

it('recusa telefone fora do padrão', function (string $entrada) {
    expect(fn () => Telefone::fromNative($entrada))->toThrow(InvalidArgumentException::class);
})->with([
    'DDD começando em zero' => '(01) 98888-7777',
    'curto demais' => '11 9888',
    'longo demais' => '11 9 8888 77777',
]);
