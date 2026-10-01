<?php

namespace Emprestimos\Application\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmprestimoRealizado extends Notification
{
    public function __construct(
        private readonly string $tituloObra,
        private readonly string $codigoPatrimonio,
        private readonly CarbonImmutable $prazoDevolucao,
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
            ->subject("Empréstimo realizado: {$this->tituloObra}")
            ->line("O exemplar {$this->codigoPatrimonio} de \"{$this->tituloObra}\" foi emprestado a você.")
            ->line("Devolva até {$this->prazoDevolucao->format('d/m/Y')}.")
            ->line('Desejamos uma boa leitura, obrigado por utilizar nossos serviços.');
    }
}
