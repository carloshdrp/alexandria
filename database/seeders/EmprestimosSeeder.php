<?php

namespace Database\Seeders;

use Acesso\Domain\Enums\UsuarioPapel;
use Acesso\Domain\Enums\UsuarioSituacao;
use Acesso\Domain\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\EmprestimoFactory;
use Database\Factories\MultaFactory;
use Emprestimos\Domain\Models\Emprestimo;
use Emprestimos\Domain\Models\EmprestimoRenovacao;
use Emprestimos\Domain\Models\Multa;
use Emprestimos\Domain\Models\Reserva;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Inventario\Domain\Enums\ExemplarMotivoBaixa;
use Inventario\Domain\Enums\ExemplarSituacao;
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;
use RuntimeException;

class EmprestimosSeeder extends Seeder
{
    private const string OBRA_VENCE_AMANHA = 'Dom Casmurro';

    private const string OBRA_EM_ANDAMENTO = 'Sapiens: Uma Breve História da Humanidade';

    private const string OBRA_ATRASADA = 'Duna';

    private const string OBRA_VENCIDA_SEM_MARCACAO = '1984';

    private const string OBRA_MULTA_PENDENTE = 'Torto Arado';

    private const string OBRA_COM_FILA = 'Grande Sertão: Veredas';

    private const string OBRA_RESERVA_DISPONIVEL = 'Eu, Robô';

    private const string OBRA_RESERVA_VENCIDA = 'Refatoração: Aperfeiçoando o Design de Códigos Existentes';

    private const string OBRA_EXEMPLAR_BAIXADO = 'Código Limpo';

    /** @var Collection<int, User>|null */
    private ?Collection $leitores = null;

    /** @var array<int, int> */
    private array $ativosPorLeitor = [];

    public function run(): void
    {
        $this->historicoDevolvido();
        $this->emprestimosEmAndamento();
        $this->multaPendente();
        $this->filaDeReserva();
        $this->reservaDisponivelParaRetirada();
        $this->reservaDisponivelVencida();
        $this->historicoDeReservas();
        $this->exemplarBaixadoComEmprestimoEncerrado();
    }

    private function historicoDevolvido(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $retiradoEm = CarbonImmutable::now()->subDays(fake()->numberBetween(20, 180))->setTime(10, 0);
            $emprestimo = $this->devolvido($this->leitor(), $this->exemplarNoAcervo(), $retiradoEm, $retiradoEm->addDays(fake()->numberBetween(3, 14)));
            $this->registrarRetorno($emprestimo);
        }

        for ($i = 0; $i < 5; $i++) {
            $retiradoEm = CarbonImmutable::now()->subDays(fake()->numberBetween(40, 180))->setTime(10, 0);
            $emprestimo = $this->devolvido($this->leitor(), $this->exemplarNoAcervo(), $retiradoEm, $retiradoEm->addDays(fake()->numberBetween(15, 28)), renovacoes: 1);
            $this->registrarRetorno($emprestimo);
        }

        for ($i = 0; $i < 4; $i++) {
            $retiradoEm = CarbonImmutable::now()->subDays(fake()->numberBetween(30, 180))->setTime(10, 0);
            $emprestimo = $this->devolvido($this->leitor(), $this->exemplarNoAcervo(), $retiradoEm, $retiradoEm->addDays(14 + fake()->numberBetween(2, 5)));
            $this->registrarRetorno($emprestimo, multaPaga: true);
        }
    }

    private function emprestimosEmAndamento(): void
    {
        $cliente = $this->persona(UsuariosSeeder::CLIENTE);
        $this->emprestar($cliente, $this->exemplarNoAcervo(self::OBRA_VENCE_AMANHA), Emprestimo::factory()->venceAmanha());
        $this->emprestar($cliente, $this->exemplarNoAcervo(self::OBRA_EM_ANDAMENTO), Emprestimo::factory()->retiradoEm(CarbonImmutable::now()->subDays(5)));

        $this->emprestar($this->persona(UsuariosSeeder::ATRASADO), $this->exemplarNoAcervo(self::OBRA_ATRASADA), Emprestimo::factory()->atrasado());

        $this->emprestar($this->leitor(), $this->exemplarNoAcervo(self::OBRA_VENCIDA_SEM_MARCACAO), Emprestimo::factory()->vencidoEmAndamento());

        for ($i = 0; $i < 7; $i++) {
            $this->emprestar(
                $this->leitor(),
                $this->exemplarNoAcervo(),
                Emprestimo::factory()->retiradoEm(CarbonImmutable::now()->subDays(fake()->numberBetween(1, 10))),
            );
        }
    }

    private function multaPendente(): void
    {
        $retiradoEm = CarbonImmutable::now()->subDays(30)->setTime(10, 0);
        $emprestimo = $this->devolvido($this->persona(UsuariosSeeder::MULTA), $this->exemplarNoAcervo(self::OBRA_MULTA_PENDENTE), $retiradoEm, $retiradoEm->addDays(19));
        $this->registrarRetorno($emprestimo);
    }

    private function filaDeReserva(): void
    {
        $obra = $this->obra(self::OBRA_COM_FILA);
        $leitoresComExemplar = [];

        foreach (Exemplar::disponivelPorObra($obra->id)->get() as $exemplar) {
            $leitor = $this->leitor();
            $leitoresComExemplar[] = $leitor->id;
            $this->emprestar($leitor, $exemplar, Emprestimo::factory()->retiradoEm(CarbonImmutable::now()->subDays(fake()->numberBetween(2, 8))));
        }

        $fila = [
            $this->persona(UsuariosSeeder::CLIENTE),
            $this->leitor(excluir: $leitoresComExemplar),
            $this->leitor(excluir: $leitoresComExemplar),
        ];

        foreach ($fila as $posicao => $leitor) {
            Reserva::factory()
                ->enfileiradaEm(CarbonImmutable::now()->subDays(3 - $posicao))
                ->create(['user_id' => $leitor->id, 'obra_id' => $obra->id]);
        }
    }

    private function reservaDisponivelParaRetirada(): void
    {
        $this->reservaDisponivel(self::OBRA_RESERVA_DISPONIVEL, $this->persona(UsuariosSeeder::RESERVA), CarbonImmutable::now()->subHours(6));
    }

    private function reservaDisponivelVencida(): void
    {
        $this->reservaDisponivel(self::OBRA_RESERVA_VENCIDA, $this->leitor(), CarbonImmutable::now()->subHours(50));
    }

    private function reservaDisponivel(string $titulo, User $leitor, CarbonImmutable $disponibilizadaEm): void
    {
        $exemplar = $this->exemplarNoAcervo($titulo);

        $this->devolvido($this->leitor(excluir: [$leitor->id]), $exemplar, $disponibilizadaEm->subDays(10), $disponibilizadaEm);

        $exemplar->emprestar();
        $exemplar->save();
        $exemplar->reservar();
        $exemplar->save();

        Reserva::factory()
            ->enfileiradaEm($disponibilizadaEm->subDays(5))
            ->disponivel($exemplar->id, $disponibilizadaEm)
            ->create(['user_id' => $leitor->id, 'obra_id' => $exemplar->obra_id]);
    }

    private function historicoDeReservas(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $exemplar = $this->exemplarNoAcervo();
            Reserva::factory()
                ->enfileiradaEm(CarbonImmutable::now()->subDays(fake()->numberBetween(30, 90)))
                ->atendida($exemplar->id, CarbonImmutable::now()->subDays(fake()->numberBetween(10, 29)))
                ->create(['user_id' => $this->leitor()->id, 'obra_id' => $exemplar->obra_id]);
        }

        $exemplar = $this->exemplarNoAcervo();
        Reserva::factory()
            ->enfileiradaEm(CarbonImmutable::now()->subDays(40))
            ->expirada($exemplar->id, CarbonImmutable::now()->subDays(20))
            ->create(['user_id' => $this->leitor()->id, 'obra_id' => $exemplar->obra_id]);

        for ($i = 0; $i < 2; $i++) {
            $exemplar = $this->exemplarNoAcervo();
            Reserva::factory()
                ->enfileiradaEm(CarbonImmutable::now()->subDays(fake()->numberBetween(15, 60)))
                ->cancelada(CarbonImmutable::now()->subDays(fake()->numberBetween(5, 14)))
                ->create(['user_id' => $this->leitor()->id, 'obra_id' => $exemplar->obra_id]);
        }
    }

    private function exemplarBaixadoComEmprestimoEncerrado(): void
    {
        $exemplar = $this->exemplarNoAcervo(self::OBRA_EXEMPLAR_BAIXADO);
        $leitor = $this->leitor();

        $exemplar->emprestar();
        $exemplar->save();

        Emprestimo::factory()
            ->retiradoEm(CarbonImmutable::now()->subDays(12))
            ->encerradoPorBaixa(CarbonImmutable::now()->subDays(2))
            ->create(['user_id' => $leitor->id, 'exemplar_id' => $exemplar->id]);

        $exemplar->baixar(ExemplarMotivoBaixa::Danificado);
        $exemplar->baixado_em = CarbonImmutable::now()->subDays(2);
        $exemplar->save();
    }

    private function emprestar(User $leitor, Exemplar $exemplar, EmprestimoFactory $factory): Emprestimo
    {
        $exemplar->emprestar();
        $exemplar->save();

        $this->ativosPorLeitor[$leitor->id] = ($this->ativosPorLeitor[$leitor->id] ?? 0) + 1;

        return $factory->createOne(['user_id' => $leitor->id, 'exemplar_id' => $exemplar->id]);
    }

    private function devolvido(User $leitor, Exemplar $exemplar, CarbonImmutable $retiradoEm, CarbonImmutable $devolvidoEm, int $renovacoes = 0): Emprestimo
    {
        $emprestimo = Emprestimo::factory()
            ->retiradoEm($retiradoEm)
            ->when($renovacoes > 0, fn (EmprestimoFactory $factory) => $factory->renovado($renovacoes))
            ->devolvido($devolvidoEm)
            ->createOne(['user_id' => $leitor->id, 'exemplar_id' => $exemplar->id]);

        for ($sequencia = 1; $sequencia <= $renovacoes; $sequencia++) {
            EmprestimoRenovacao::factory()->doEmprestimo($emprestimo, $sequencia)->create();
        }

        return $emprestimo;
    }

    private function registrarRetorno(Emprestimo $emprestimo, bool $multaPaga = false): void
    {
        if (! $emprestimo->prazo->estaAtrasado($emprestimo->devolvido_em)) {
            return;
        }

        Multa::factory()
            ->doEmprestimo($emprestimo)
            ->when($multaPaga, fn (MultaFactory $factory) => $factory->paga($emprestimo->devolvido_em?->addDay()))
            ->create();
    }

    /**
     * @param  list<int>  $excluir
     */
    private function leitor(array $excluir = []): User
    {
        $this->leitores ??= User::where('papel', UsuarioPapel::Cliente)
            ->where('situacao', UsuarioSituacao::Ativo)
            ->whereNotIn('email', UsuariosSeeder::personas())
            ->get();

        $candidatos = $this->leitores
            ->reject(fn (User $leitor) => in_array($leitor->id, $excluir, true))
            ->reject(fn (User $leitor) => ($this->ativosPorLeitor[$leitor->id] ?? 0) >= Emprestimo::MAX_ATIVOS_POR_USUARIO)
            ->values();

        $leitor = fake()->randomElement($candidatos->all());

        if (! $leitor instanceof User) {
            throw new RuntimeException('Não há leitor fictício disponível para o cenário.');
        }

        return $leitor;
    }

    private function persona(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function obra(string $titulo): Obra
    {
        return Obra::where('titulo', $titulo)->firstOrFail();
    }

    private function exemplarNoAcervo(?string $titulo = null): Exemplar
    {
        $query = Exemplar::query()->where('situacao', ExemplarSituacao::NoAcervo);

        if ($titulo !== null) {
            return $query->where('obra_id', $this->obra($titulo)->id)->firstOrFail();
        }

        $reservadas = Obra::whereIn('titulo', [
            self::OBRA_COM_FILA,
            self::OBRA_RESERVA_DISPONIVEL,
            self::OBRA_RESERVA_VENCIDA,
            self::OBRA_EXEMPLAR_BAIXADO,
        ])->pluck('id');

        $exemplar = fake()->randomElement($query->whereNotIn('obra_id', $reservadas)->get()->all());

        if (! $exemplar instanceof Exemplar) {
            throw new RuntimeException('Não há exemplar no acervo para o cenário.');
        }

        return $exemplar;
    }
}
