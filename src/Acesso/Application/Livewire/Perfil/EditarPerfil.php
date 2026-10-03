<?php

namespace Acesso\Application\Livewire\Perfil;

use Acesso\Application\Actions\UpdateUserPassword;
use Acesso\Application\Actions\UpdateUserProfileInformation;
use Acesso\Domain\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Title('Meu perfil')]
class EditarPerfil extends Component
{
    public string $name = '';

    public string $email = '';

    public string $documento = '';

    public string $telefone = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $usuario = $this->usuario();

        $this->name = $usuario->name;
        $this->email = $usuario->email;
        $this->documento = $usuario->documento->formatado();
        $this->telefone = (string) $usuario->telefone;
    }

    private function usuario(): User
    {
        $usuario = auth()->user();

        if (! $usuario instanceof User) {
            throw new RuntimeException('Nenhum usuário autenticado.');
        }

        return $usuario;
    }

    public function salvar(UpdateUserProfileInformation $acao): void
    {
        $usuario = $this->usuario();
        $emailAnterior = $usuario->email;

        $acao->update($usuario, [
            'name' => $this->name,
            'email' => $this->email,
            'documento' => $this->documento,
            'telefone' => $this->telefone === '' ? null : $this->telefone,
        ]);

        session()->flash('sucesso', $usuario->email === $emailAnterior
            ? 'Perfil atualizado.'
            : 'Perfil atualizado. Confirme o novo e-mail pelo link que enviamos.');
    }

    public function alterarSenha(UpdateUserPassword $acao): void
    {
        $acao->update($this->usuario(), [
            'current_password' => $this->current_password,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        session()->flash('sucesso', 'Senha alterada.');
    }

    public function render(): View
    {
        return view('acesso::livewire.perfil.editar-perfil', [
            'usuario' => $this->usuario(),
        ]);
    }
}
