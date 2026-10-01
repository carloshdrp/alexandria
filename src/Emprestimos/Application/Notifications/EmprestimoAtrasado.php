<?php

namespace Emprestimos\Application\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmprestimoAtrasado extends Notification
{
    public function __construct(
        private readonly string $tituloObra,
        private readonly CarbonImmutable $prazoDevolucao,
        private readonly int $diasAtraso,
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
        $dias = $this->diasAtraso === 1 ? '1 dia' : "{$this->diasAtraso} dias";

        return (new MailMessage)
            ->subject("Devolução em atraso: {$this->tituloObra}")
            ->line("O empréstimo de \"{$this->tituloObra}\" venceu em {$this->prazoDevolucao->format('d/m/Y')} e está {$dias} em atraso.")
            ->line('Cada dia de atraso gera multa de R$ 1,00, e multa pendente bloqueia novos empréstimos.');
    }
}
