<?php

use App\Events\DomainEvent;
use App\Events\IntegrationEvent;

arch('Empréstimos consome o Inventário só pela linguagem publicada, nunca pelos models')
    ->expect('Emprestimos')
    ->not->toUse('Inventario\Domain\Models');

arch('Inventário não conhece Empréstimos')
    ->expect('Inventario')
    ->not->toUse('Emprestimos');

arch('o shared kernel não conhece os Bounded Contexts')
    ->expect(['App', 'Acesso'])
    ->not->toUse(['Emprestimos', 'Inventario']);

arch('os Bounded Contexts não usam o interior do Acesso, só o modelo que ele publica')
    ->expect(['Acesso\Application', 'Acesso\Infrastructure', 'Acesso\Interface'])
    ->not->toBeUsedIn(['Emprestimos', 'Inventario']);

arch('todo evento de domínio dos Bounded Contexts implementa DomainEvent')
    ->expect(['Emprestimos\Domain\Events', 'Inventario\Domain\Events'])
    ->toImplement(DomainEvent::class)
    ->ignoring('Inventario\Domain\Events\Integracao');

arch('todo evento de integração implementa IntegrationEvent')
    ->expect('Inventario\Domain\Events\Integracao')
    ->toImplement(IntegrationEvent::class);

arch('todo evento dos Bounded Contexts é final')
    ->expect(['Emprestimos\Domain\Events', 'Inventario\Domain\Events'])
    ->toBeFinal();

arch('todo evento dos Bounded Contexts é readonly')
    ->expect(['Emprestimos\Domain\Events', 'Inventario\Domain\Events'])
    ->toBeReadonly();

arch('evento de integração não carrega entidade do próprio Bounded Context')
    ->expect('Inventario\Domain\Events\Integracao')
    ->not->toUse('Inventario\Domain\Models');

arch('Empréstimos só enxerga os eventos de integração do Inventário, nunca os de domínio')
    ->expect('Inventario\Domain\Events')
    ->not->toBeUsedIn('Emprestimos')
    ->ignoring('Inventario\Domain\Events\Integracao');

arch('os eventos do Acesso implementam DomainEvent')
    ->expect('Acesso\Domain\Events')
    ->toImplement(DomainEvent::class);

arch('os eventos do Acesso são final')
    ->expect('Acesso\Domain\Events')
    ->toBeFinal();

arch('os eventos do Acesso são readonly')
    ->expect('Acesso\Domain\Events')
    ->toBeReadonly();
