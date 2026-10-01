<?php

namespace Emprestimos\Application\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservaReenfileirada extends Notification
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
            ->subject("Reserva atualizada: {$this->tituloObra}")
            ->line("O exemplar de \"{$this->tituloObra}\" separado para você foi baixado do acervo, e a retirada não pode ser mantida.")
            ->line('Entretanto, não se preocupe. Nenhuma ação é necessária, avisaremos assim que um exemplar ficar disponível.');
    }
}
