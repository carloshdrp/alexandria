<?php

use App\Acesso\Application\Livewire\Clientes\CadastroCliente;
use App\Acesso\Application\Livewire\Clientes\Clientes;
use App\Acesso\Application\Livewire\Clientes\EditarCliente;
use App\Acesso\Application\Livewire\Perfil\EditarPerfil;
use Illuminate\Support\Facades\Route;

Route::prefix('clientes')->name('clientes.')->group(function () {
    Route::get('/', Clientes::class)->name('index');
    Route::get('/novo', CadastroCliente::class)->name('novo');
    Route::get('/{user}/editar', EditarCliente::class)->whereNumber('user')->name('editar');
});

Route::get('/perfil', EditarPerfil::class)->name('perfil');
