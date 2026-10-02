<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsuariosSeeder extends Seeder
{
    public const string BIBLIOTECARIO = 'bibliotecario@alexandria.test';

    public const string CLIENTE = 'carlospereira.gr016@academico.ifsul.edu.br';

    public const string ATRASADO = 'atrasado@alexandria.test';

    public const string MULTA = 'multa@alexandria.test';

    public const string RESERVA = 'reserva@alexandria.test';

    public const string BLOQUEADO = 'bloqueado@alexandria.test';

    public const string NAO_VERIFICADO = 'naoverificado@alexandria.test';

    public const int CLIENTES_ALEATORIOS = 15;

    public function run(): void
    {
        User::factory()->bibliotecario()->create(['name' => 'Bibliotecário', 'email' => self::BIBLIOTECARIO]);
        User::factory()->create(['name' => 'Cliente', 'email' => self::CLIENTE]);
        User::factory()->create(['name' => 'Ana Beatriz Ramos', 'email' => self::ATRASADO]);
        User::factory()->create(['name' => 'Carlos Eduardo Lima', 'email' => self::MULTA]);
        User::factory()->create(['name' => 'Rafael Nogueira', 'email' => self::RESERVA]);
        User::factory()->bloqueado()->create(['name' => 'Bruno Ferraz', 'email' => self::BLOQUEADO]);
        User::factory()->naoVerificado()->create(['name' => 'Luísa Prado', 'email' => self::NAO_VERIFICADO]);

        User::factory()->count(self::CLIENTES_ALEATORIOS)->create();
    }

    /**
     * @return list<string>
     */
    public static function personas(): array
    {
        return [
            self::BIBLIOTECARIO,
            self::CLIENTE,
            self::ATRASADO,
            self::MULTA,
            self::RESERVA,
            self::BLOQUEADO,
            self::NAO_VERIFICADO,
        ];
    }
}
