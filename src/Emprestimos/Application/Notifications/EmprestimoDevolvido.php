<?php

namespace Emprestimos\Application\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmprestimoDevolvido extends Notification
{
    public function __construct(
        private readonly string $tituloObra,
        private readonly CarbonImmutable $devolvidoEm,
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
            ->subject("Devolução registrada: {$this->tituloObra}")
            ->line("A devolução de \"{$this->tituloObra}\" foi registrada em {$this->devolvidoEm->format('d/m/Y H:i')}.")
            ->line('O empréstimo está encerrado, agradecemos por utilizar nossos serviços.');
    }
}
