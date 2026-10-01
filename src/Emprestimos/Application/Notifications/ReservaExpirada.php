<?php

namespace Emprestimos\Application\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservaExpirada extends Notification
{
    public function __construct(
        private readonly string $tituloObra,
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
        return (new MailMessage)
            ->subject("Reserva expirada: {$this->tituloObra}")
            ->line("O prazo de 48 horas para retirar \"{$this->tituloObra}\" terminou, e a reserva expirou.")
            ->line('Você pode reservar a obra novamente.');
    }
}
