<?php

namespace Emprestimos\Application\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservaCanceladaPorIndisponibilidade extends Notification
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
            ->line("A biblioteca não tem mais nenhum exemplar de \"{$this->tituloObra}\" no acervo.")
            ->line('Sua reserva foi cancelada automaticamente, porque não há como atendê-la.')
            ->line('Pedimos desculpas pelo inconveniente e convidamos você a buscar por outras obras em nosso catálogo.');
    }
}
