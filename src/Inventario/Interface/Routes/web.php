<?php

use Illuminate\Support\Facades\Route;
use Inventario\Application\Http\Controllers\AtualizacaoObraController;
use Inventario\Application\Http\Controllers\BaixaExemplarController;
use Inventario\Application\Http\Controllers\CadastroAutorController;
use Inventario\Application\Http\Controllers\CadastroCategoriaController;
use Inventario\Application\Http\Controllers\CadastroEditoraController;
use Inventario\Application\Http\Controllers\CadastroExemplarController;
use Inventario\Application\Http\Controllers\CadastroObraController;
use Inventario\Application\Http\Controllers\RemocaoObraController;

Route::post('/obras', CadastroObraController::class);
Route::post('/obras/{obra}', AtualizacaoObraController::class);
Route::delete('/obras/{obra}', RemocaoObraController::class);
Route::post('/obras/{obra}/exemplares', CadastroExemplarController::class);

Route::post('/exemplares/{exemplar}/baixa', BaixaExemplarController::class);

Route::post('/autores', CadastroAutorController::class);
Route::post('/editoras', CadastroEditoraController::class);
Route::post('/categorias', CadastroCategoriaController::class);
