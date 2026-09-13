<?php

use Emprestimos\Application\Http\Controllers\CancelamentoReservaController;
use Emprestimos\Application\Http\Controllers\DevolucaoEmprestimoController;
use Emprestimos\Application\Http\Controllers\PagamentoMultaController;
use Emprestimos\Application\Http\Controllers\RealizacaoEmprestimoController;
use Emprestimos\Application\Http\Controllers\RenovacaoEmprestimoController;
use Emprestimos\Application\Http\Controllers\ReservaObraController;
use Illuminate\Support\Facades\Route;

Route::post('/', RealizacaoEmprestimoController::class);
Route::post('/{emprestimo}/devolucao', DevolucaoEmprestimoController::class);
Route::post('/{emprestimo}/renovacao', RenovacaoEmprestimoController::class);

Route::post('/reservas', ReservaObraController::class);
Route::post('/reservas/{reserva}/cancelamento', CancelamentoReservaController::class);

Route::post('/multas/{multa}/pagamento', PagamentoMultaController::class);
