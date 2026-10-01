<?php

namespace Emprestimos\Application\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VencimentoProximo extends Notification
{
    public function __construct(
        private readonly string $tituloObra,
        private readonly CarbonImmutable $prazoDevolucao,
        private readonly bool $podeRenovar,
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
        $mensagem = (new MailMessage)
            ->subject("Devolução se aproxima: {$this->tituloObra}")
            ->line("O prazo de \"{$this->tituloObra}\" termina em {$this->prazoDevolucao->format('d/m/Y')}.");

        $mensagem = $this->podeRenovar
            ? $mensagem->line('Você já pode renovar o empréstimo pelo site ou devolver o exemplar no balcão.')
            : $mensagem->line('Este empréstimo não pode mais ser renovado. Devolva o exemplar no balcão dentro o prazo.');

        return $mensagem->line('Depois do prazo, cada dia de atraso gera multa de R$ 1,00.');
    }
}
