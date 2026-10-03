<?php

use Acesso\Application\Livewire\Clientes\CadastroCliente;
use Acesso\Application\Livewire\Clientes\Clientes;
use Acesso\Application\Livewire\Clientes\EditarCliente;
use Acesso\Application\Livewire\Perfil\EditarPerfil;
use Illuminate\Support\Facades\Route;

Route::middleware(['verified', 'can:bibliotecario'])->prefix('clientes')->name('clientes.')->group(function () {
    Route::get('/', Clientes::class)->name('index');
    Route::get('/novo', CadastroCliente::class)->name('novo');
    Route::get('/{user}/editar', EditarCliente::class)->whereNumber('user')->name('editar');
});

Route::get('/perfil', EditarPerfil::class)->name('perfil');
