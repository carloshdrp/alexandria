<?php

use Illuminate\Support\Facades\Route;
use Inventario\Application\Livewire\Autores;
use Inventario\Application\Livewire\Categorias;
use Inventario\Application\Livewire\Editoras;
use Inventario\Application\Livewire\Exemplares;
use Inventario\Application\Livewire\ExemplaresDaObra;
use Inventario\Application\Livewire\FormularioObra;
use Inventario\Application\Livewire\Obras;

Route::get('/obras', Obras::class)->name('obras');
Route::get('/obras/nova', FormularioObra::class)->name('obras.nova');
Route::get('/obras/{obra}/editar', FormularioObra::class)->name('obras.editar');
Route::get('/obras/{obra}/exemplares', ExemplaresDaObra::class)->name('obras.exemplares');
Route::get('/exemplares', Exemplares::class)->name('exemplares');

Route::get('/autores', Autores::class)->name('autores');
Route::get('/editoras', Editoras::class)->name('editoras');
Route::get('/categorias', Categorias::class)->name('categorias');
