<?php

namespace Database\Seeders;

use Acesso\Domain\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Inventario\Domain\Models\Autor;
use Inventario\Domain\Models\Categoria;
use Inventario\Domain\Models\Editora;
use Inventario\Domain\Models\Exemplar;
use Inventario\Domain\Models\Obra;
use Inventario\Domain\Services\ArmazenadorCapaObra;
use Inventario\Domain\ValueObjects\CodigoPatrimonio;

class InventarioSeeder extends Seeder
{
    private const array CATEGORIAS = [
        'Literatura Brasileira',
        'Literatura Estrangeira',
        'Ficção Científica',
        'Fantasia',
        'História',
        'Tecnologia',
        'Filosofia',
        'Infantojuvenil',
    ];

    private const array EDITORAS = [
        'Companhia das Letras',
        'Record',
        'Rocco',
        'Aleph',
        'Intrínseca',
        'Martins Fontes',
        'Novatec',
        'Bookman',
    ];

    /**
     * @var list<array{titulo: string, autores: list<string>, editora: string, categoria: string, ano: int|null, capa: string, exemplares: int, sem_isbn?: bool}>
     */
    private const array OBRAS = [
        ['titulo' => 'Dom Casmurro', 'autores' => ['Machado de Assis'], 'editora' => 'Companhia das Letras', 'categoria' => 'Literatura Brasileira', 'ano' => 1899, 'capa' => 'dom-casmurro.jpg', 'exemplares' => 3],
        ['titulo' => 'Memórias Póstumas de Brás Cubas', 'autores' => ['Machado de Assis'], 'editora' => 'Companhia das Letras', 'categoria' => 'Literatura Brasileira', 'ano' => 1881, 'capa' => 'memorias-postumas-de-bras-cubas.jpg', 'exemplares' => 2],
        ['titulo' => 'Grande Sertão: Veredas', 'autores' => ['João Guimarães Rosa'], 'editora' => 'Companhia das Letras', 'categoria' => 'Literatura Brasileira', 'ano' => 1956, 'capa' => 'grande-sertao-veredas.jpg', 'exemplares' => 2],
        ['titulo' => 'A Hora da Estrela', 'autores' => ['Clarice Lispector'], 'editora' => 'Rocco', 'categoria' => 'Literatura Brasileira', 'ano' => 1977, 'capa' => 'a-hora-da-estrela.jpg', 'exemplares' => 2],
        ['titulo' => 'Capitães da Areia', 'autores' => ['Jorge Amado'], 'editora' => 'Companhia das Letras', 'categoria' => 'Literatura Brasileira', 'ano' => 1937, 'capa' => 'capitaes-da-areia.jpg', 'exemplares' => 3],
        ['titulo' => 'Triste Fim de Policarpo Quaresma', 'autores' => ['Lima Barreto'], 'editora' => 'Record', 'categoria' => 'Literatura Brasileira', 'ano' => 1915, 'capa' => 'triste-fim-de-policarpo-quaresma.jpg', 'exemplares' => 1],
        ['titulo' => 'Torto Arado', 'autores' => ['Itamar Vieira Junior'], 'editora' => 'Companhia das Letras', 'categoria' => 'Literatura Brasileira', 'ano' => 2019, 'capa' => 'torto-arado.jpg', 'exemplares' => 4],
        ['titulo' => "Olhos d'Água", 'autores' => ['Conceição Evaristo'], 'editora' => 'Record', 'categoria' => 'Literatura Brasileira', 'ano' => 2014, 'capa' => 'olhos-dagua.jpg', 'exemplares' => 2],
        ['titulo' => 'Reinações de Narizinho', 'autores' => ['Monteiro Lobato'], 'editora' => 'Companhia das Letras', 'categoria' => 'Infantojuvenil', 'ano' => 1931, 'capa' => 'reinacoes-de-narizinho.jpg', 'exemplares' => 2],
        ['titulo' => 'O Pequeno Príncipe', 'autores' => ['Antoine de Saint-Exupéry'], 'editora' => 'Record', 'categoria' => 'Infantojuvenil', 'ano' => 1943, 'capa' => 'o-pequeno-principe.jpg', 'exemplares' => 3],
        ['titulo' => '1984', 'autores' => ['George Orwell'], 'editora' => 'Companhia das Letras', 'categoria' => 'Literatura Estrangeira', 'ano' => 1949, 'capa' => '1984.jpg', 'exemplares' => 3],
        ['titulo' => 'Orgulho e Preconceito', 'autores' => ['Jane Austen'], 'editora' => 'Martins Fontes', 'categoria' => 'Literatura Estrangeira', 'ano' => 1813, 'capa' => 'orgulho-e-preconceito.jpg', 'exemplares' => 2],
        ['titulo' => 'Ensaio sobre a Cegueira', 'autores' => ['José Saramago'], 'editora' => 'Companhia das Letras', 'categoria' => 'Literatura Estrangeira', 'ano' => 1995, 'capa' => 'ensaio-sobre-a-cegueira.jpg', 'exemplares' => 2],
        ['titulo' => 'Frankenstein', 'autores' => ['Mary Shelley'], 'editora' => 'Martins Fontes', 'categoria' => 'Literatura Estrangeira', 'ano' => 1818, 'capa' => 'frankenstein.jpg', 'exemplares' => 1],
        ['titulo' => 'Americanah', 'autores' => ['Chimamanda Ngozi Adichie'], 'editora' => 'Companhia das Letras', 'categoria' => 'Literatura Estrangeira', 'ano' => 2013, 'capa' => 'americanah.jpg', 'exemplares' => 2],
        ['titulo' => 'Fundação', 'autores' => ['Isaac Asimov'], 'editora' => 'Aleph', 'categoria' => 'Ficção Científica', 'ano' => 1951, 'capa' => 'fundacao.jpg', 'exemplares' => 2],
        ['titulo' => 'Eu, Robô', 'autores' => ['Isaac Asimov'], 'editora' => 'Aleph', 'categoria' => 'Ficção Científica', 'ano' => 1950, 'capa' => 'eu-robo.jpg', 'exemplares' => 1],
        ['titulo' => 'Duna', 'autores' => ['Frank Herbert'], 'editora' => 'Aleph', 'categoria' => 'Ficção Científica', 'ano' => 1965, 'capa' => 'duna.jpg', 'exemplares' => 3],
        ['titulo' => 'A Mão Esquerda da Escuridão', 'autores' => ['Ursula K. Le Guin'], 'editora' => 'Aleph', 'categoria' => 'Ficção Científica', 'ano' => 1969, 'capa' => 'a-mao-esquerda-da-escuridao.jpg', 'exemplares' => 1],
        ['titulo' => 'O Guia do Mochileiro das Galáxias', 'autores' => ['Douglas Adams'], 'editora' => 'Record', 'categoria' => 'Ficção Científica', 'ano' => 1979, 'capa' => 'o-guia-do-mochileiro-das-galaxias.jpg', 'exemplares' => 2],
        ['titulo' => 'O Hobbit', 'autores' => ['J. R. R. Tolkien'], 'editora' => 'Martins Fontes', 'categoria' => 'Fantasia', 'ano' => 1937, 'capa' => 'o-hobbit.jpg', 'exemplares' => 2],
        ['titulo' => 'O Senhor dos Anéis: A Sociedade do Anel', 'autores' => ['J. R. R. Tolkien'], 'editora' => 'Martins Fontes', 'categoria' => 'Fantasia', 'ano' => 1954, 'capa' => 'o-senhor-dos-aneis-a-sociedade-do-anel.jpg', 'exemplares' => 2],
        ['titulo' => 'Sapiens: Uma Breve História da Humanidade', 'autores' => ['Yuval Noah Harari'], 'editora' => 'Companhia das Letras', 'categoria' => 'História', 'ano' => 2011, 'capa' => 'sapiens.jpg', 'exemplares' => 3],
        ['titulo' => '1808', 'autores' => ['Laurentino Gomes'], 'editora' => 'Intrínseca', 'categoria' => 'História', 'ano' => 2007, 'capa' => '1808.jpg', 'exemplares' => 2],
        ['titulo' => 'Domain-Driven Design: Atacando as Complexidades no Coração do Software', 'autores' => ['Eric Evans'], 'editora' => 'Bookman', 'categoria' => 'Tecnologia', 'ano' => 2003, 'capa' => 'domain-driven-design.jpg', 'exemplares' => 2],
        ['titulo' => 'Padrões de Projeto: Soluções Reutilizáveis de Software Orientado a Objetos', 'autores' => ['Erich Gamma', 'Richard Helm', 'Ralph Johnson', 'John Vlissides'], 'editora' => 'Bookman', 'categoria' => 'Tecnologia', 'ano' => 1994, 'capa' => 'padroes-de-projeto.jpg', 'exemplares' => 2],
        ['titulo' => 'Refatoração: Aperfeiçoando o Design de Códigos Existentes', 'autores' => ['Martin Fowler'], 'editora' => 'Novatec', 'categoria' => 'Tecnologia', 'ano' => 1999, 'capa' => 'refatoracao.jpg', 'exemplares' => 1],
        ['titulo' => 'Código Limpo', 'autores' => ['Robert C. Martin'], 'editora' => 'Novatec', 'categoria' => 'Tecnologia', 'ano' => 2008, 'capa' => 'codigo-limpo.jpg', 'exemplares' => 3],
        ['titulo' => 'A República', 'autores' => ['Platão'], 'editora' => 'Martins Fontes', 'categoria' => 'Filosofia', 'ano' => null, 'capa' => 'a-republica.jpg', 'exemplares' => 2, 'sem_isbn' => true],
        ['titulo' => 'Meditações', 'autores' => ['Marco Aurélio'], 'editora' => 'Martins Fontes', 'categoria' => 'Filosofia', 'ano' => null, 'capa' => 'meditacoes.jpg', 'exemplares' => 1],
    ];

    public function run(ArmazenadorCapaObra $armazenadorCapa): void
    {
        $armazenadorCapa->esvaziar();

        $bibliotecario = User::where('email', UsuariosSeeder::BIBLIOTECARIO)->firstOrFail();

        $categorias = $this->porNome(Categoria::factory(), self::CATEGORIAS, $bibliotecario);
        $editoras = $this->porNome(Editora::factory(), self::EDITORAS, $bibliotecario);
        $autores = $this->porNome(
            Autor::factory(),
            collect(self::OBRAS)->flatMap(fn (array $obra) => $obra['autores'])->unique()->values()->all(),
            $bibliotecario,
        );

        $patrimonio = 0;

        foreach (self::OBRAS as $dados) {
            $obra = Obra::factory()
                ->when($dados['sem_isbn'] ?? false, fn ($factory) => $factory->semIsbn())
                ->create([
                    'titulo' => $dados['titulo'],
                    'editora_id' => $editoras[$dados['editora']]->id,
                    'categoria_id' => $categorias[$dados['categoria']]->id,
                    'ano_publicacao' => $dados['ano'],
                    'user_id' => $bibliotecario->id,
                ]);

            $obra->definirAutores(
                array_map(fn (string $nome) => $autores[$nome]->id, $dados['autores']),
                $bibliotecario->id,
            );

            $obra->capa_path = $armazenadorCapa->armazenar(
                new UploadedFile(database_path("seeders/capas/{$dados['capa']}"), $dados['capa'], test: true),
                $obra,
            );
            $obra->capa_miniatura_path = $armazenadorCapa->gerarMiniatura($obra);
            $obra->save();

            for ($i = 0; $i < $dados['exemplares']; $i++) {
                Exemplar::factory()->create([
                    'obra_id' => $obra->id,
                    'codigo_patrimonio' => CodigoPatrimonio::fromNative(sprintf('EX-%06d', ++$patrimonio)),
                    'user_id' => $bibliotecario->id,
                ]);
            }
        }
    }

    /**
     * @template TModel of Model
     *
     * @param  Factory<TModel>  $factory
     * @param  array<int, string>  $nomes
     * @return array<string, TModel>
     */
    private function porNome(Factory $factory, array $nomes, User $bibliotecario): array
    {
        $modelos = [];

        foreach ($nomes as $nome) {
            $modelos[$nome] = $factory->createOne(['nome' => $nome, 'user_id' => $bibliotecario->id]);
        }

        return $modelos;
    }
}
