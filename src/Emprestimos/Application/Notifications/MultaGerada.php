<?php

namespace Emprestimos\Application\Notifications;

use Emprestimos\Domain\ValueObjects\Dinheiro;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MultaGerada extends Notification
{
    public function __construct(
        private readonly string $tituloObra,
        private readonly Dinheiro $valor,
        private readonly int $diasAtraso,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $dias = $this->diasAtraso === 1 ? '1 dia' : "{$this->diasAtraso} dias";
        $valor = number_format($this->valor->getNativeValue(), 2, ',', '.');

        return (new MailMessage)
            ->subject("Multa gerada: {$this->tituloObra}")
            ->line("A devolução de \"{$this->tituloObra}\" teve {$dias} de atraso.")
            ->line("Foi gerada uma multa de R$ {$valor}.")
            ->line('Enquanto a multa estiver pendente, você não pode realizar novos empréstimos nem renovar os atuais.');
    }
}
