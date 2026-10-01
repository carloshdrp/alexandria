<?php

namespace Emprestimos\Application\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservaCancelada extends Notification
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
            ->subject("Reserva cancelada: {$this->tituloObra}")
            ->line("Sua reserva de \"{$this->tituloObra}\" foi cancelada.")
            ->line('Você pode reservar a obra novamente quando quiser.');
    }
}
