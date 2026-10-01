<?php

use App\Acesso\Application\Providers\AcessoServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TelescopeServiceProvider;
use Emprestimos\Application\Providers\EmprestimosServiceProvider;
use Inventario\Application\Providers\InventarioServiceProvider;

return [
    AppServiceProvider::class,
    AcessoServiceProvider::class,
    HorizonServiceProvider::class,
    TelescopeServiceProvider::class,
    EmprestimosServiceProvider::class,
    InventarioServiceProvider::class,
];
