<?php

namespace Emprestimos\Application\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmprestimoRenovado extends Notification
{
    public function __construct(
        private readonly string $tituloObra,
        private readonly CarbonImmutable $prazoDevolucao,
        private readonly int $renovacoesRestantes,
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
        $restantes = $this->renovacoesRestantes === 1
            ? 'Resta 1 renovação para este empréstimo.'
            : "Restam {$this->renovacoesRestantes} renovações para este empréstimo.";

        return (new MailMessage)
            ->subject("Empréstimo renovado: {$this->tituloObra}")
            ->line("O prazo de \"{$this->tituloObra}\" foi estendido até {$this->prazoDevolucao->format('d/m/Y')}.")
            ->line($this->renovacoesRestantes === 0 ? 'Este empréstimo não pode mais ser renovado.' : $restantes)
            ->line('Desejamos uma boa leitura, obrigado por utilizar nossos serviços.');
    }
}
