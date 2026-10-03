<?php

namespace Acesso\Application\Livewire\Clientes;

use Acesso\Application\Actions\UpdateUserProfileInformation;
use Acesso\Domain\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Editar cliente')]
class EditarCliente extends Component
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $documento = '';

    public string $telefone = '';

    public function mount(User $user): void
    {
        abort_if($user->ehBibliotecario(), 404);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->documento = $user->documento->formatado();
        $this->telefone = (string) $user->telefone;
    }

    public function salvar(UpdateUserProfileInformation $acao): void
    {
        $cliente = $this->cliente();
        $emailAnterior = $cliente->email;

        $acao->update($cliente, [
            'name' => $this->name,
            'email' => $this->email,
            'documento' => $this->documento,
            'telefone' => $this->telefone === '' ? null : $this->telefone,
        ]);

        session()->flash('sucesso', $cliente->email === $emailAnterior
            ? 'Cadastro atualizado.'
            : 'Cadastro atualizado. O cliente precisa confirmar o novo e-mail.');
    }

    private function cliente(): User
    {
        abort_if($this->user === null, 404);

        return $this->user;
    }

    public function enviarLinkDeSenha(): void
    {
        $cliente = $this->cliente();
        $status = Password::sendResetLink(['email' => $cliente->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            $this->addError('dominio', 'Não foi possível enviar o link de recuperação.');

            return;
        }

        session()->flash('sucesso', "Link de recuperação enviado para {$cliente->email}.");
    }

    public function render(): View
    {
        return view('acesso::livewire.clientes.editar-cliente', [
            'cliente' => $this->cliente(),
        ]);
    }
}
