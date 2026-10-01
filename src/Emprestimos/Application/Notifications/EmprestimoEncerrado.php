<?php

namespace Emprestimos\Application\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmprestimoEncerrado extends Notification
{
    public function __construct(
        private readonly string $tituloObra,
        private readonly string $codigoPatrimonio,
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
            ->subject("Empréstimo encerrado: {$this->tituloObra}")
            ->line("O exemplar {$this->codigoPatrimonio} de \"{$this->tituloObra}\" foi baixado do acervo, e por isso seu empréstimo foi encerrado.")
            ->line('Não é mais necessário devolvê-lo. Procure a biblioteca se tiver dúvidas sobre a baixa.');
    }
}
