<?php

namespace App\Acesso\Application\Livewire\Clientes;

use App\Acesso\Application\Actions\CreateNewUser;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Novo cliente')]
class CadastroCliente extends Component
{
    public string $name = '';

    public string $email = '';

    public string $documento = '';

    public string $telefone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function cadastrar(CreateNewUser $criador): void
    {
        $cliente = $criador->create([
            'name' => $this->name,
            'email' => $this->email,
            'documento' => $this->documento,
            'telefone' => $this->telefone === '' ? null : $this->telefone,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        $cliente->markEmailAsVerified();

        session()->flash('sucesso', "Cliente {$cliente->name} cadastrado.");

        $this->redirectRoute('emprestimos.clientes.situacao', $cliente);
    }

    public function render(): View
    {
        return view('acesso::livewire.clientes.cadastro-cliente');
    }
}
