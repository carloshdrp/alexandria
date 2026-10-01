<?php

namespace Emprestimos\Application\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservaDisponivel extends Notification
{
    public function __construct(
        private readonly string $tituloObra,
        private readonly CarbonImmutable $expiraEm,
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
            ->subject("Exemplar disponível: {$this->tituloObra}")
            ->line("Um exemplar de \"{$this->tituloObra}\" está disponível para retirada.")
            ->line("Retire até {$this->expiraEm->format('d/m/Y H:i')}, ou a reserva será expirada.");
    }
}
