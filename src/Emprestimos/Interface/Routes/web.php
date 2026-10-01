<?php

use Emprestimos\Application\Livewire\Catalogo;
use Emprestimos\Application\Livewire\EmprestimosEmAberto;
use Emprestimos\Application\Livewire\FichaDaObra;
use Emprestimos\Application\Livewire\MeusEmprestimos;
use Emprestimos\Application\Livewire\MinhasMultas;
use Emprestimos\Application\Livewire\MinhasReservas;
use Emprestimos\Application\Livewire\MultasPendentes;
use Emprestimos\Application\Livewire\ReservasAtivas;
use Emprestimos\Application\Livewire\SituacaoDoCliente;
use Illuminate\Support\Facades\Route;

Route::get('/catalogo', Catalogo::class)->name('catalogo');
Route::get('/catalogo/{obra}', FichaDaObra::class)->whereNumber('obra')->name('catalogo.obra');

Route::get('/meus', MeusEmprestimos::class)->name('meus');
Route::get('/minhas-reservas', MinhasReservas::class)->name('minhas-reservas');
Route::get('/minhas-multas', MinhasMultas::class)->name('minhas-multas');

Route::get('/', EmprestimosEmAberto::class)->name('em-aberto');
Route::get('/reservas', ReservasAtivas::class)->name('reservas');
Route::get('/multas', MultasPendentes::class)->name('multas');
Route::get('/clientes/{user}', SituacaoDoCliente::class)->name('clientes.situacao');
