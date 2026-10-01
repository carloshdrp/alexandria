<?php

namespace Emprestimos\Application\Notifications;

use Emprestimos\Domain\ValueObjects\Dinheiro;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MultaPaga extends Notification
{
    public function __construct(
        private readonly string $tituloObra,
        private readonly Dinheiro $valor,
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
        $valor = number_format($this->valor->getNativeValue(), 2, ',', '.');

        return (new MailMessage)
            ->subject("Multa paga: {$this->tituloObra}")
            ->line("O pagamento da multa pelo atraso na devolução da obra \"{$this->tituloObra}\" no valor de $valor foi realizado com sucesso.")
            ->line('Agradecemos por regularizar sua situação.');
    }
}
