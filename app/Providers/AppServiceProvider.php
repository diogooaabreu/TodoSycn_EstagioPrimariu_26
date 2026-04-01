<?php
/**
 * ============================================================
 * PROVIDER: AppServiceProvider
 * ============================================================
 * Service Provider principal da aplicação.
 * É o PRIMEIRO código PHP que corre quando a app inicia.
 *
 * Função neste projecto:
 * Força a ligação à base de dados MySQL quando a app está
 * a correr em Linux (servidor Railway ou Android).
 *
 * PORQUÊ É NECESSÁRIO:
 * O Railway usava config:cache durante o build, antes das
 * variáveis de ambiente estarem disponíveis. Isso guardava
 * 'sqlite' em cache em vez de 'mysql'. Esta linha força
 * sempre 'mysql' em ambiente Linux, independentemente do cache.
 *
 * LIMITAÇÃO CONHECIDA:
 * O Android também é Linux mas não tem o driver pdo_mysql.
 * Por isso, a app no Android não consegue ligar ao MySQL
 * directamente — esta é uma limitação do NativePHP Mobile.
 * ============================================================
 */
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register: registar serviços no container de injecção de dependências.
     * Não usado neste projecto.
     */
    public function register(): void
    {
        //
    }

    /**
     * Boot: código que corre após todos os providers serem registados.
     * É aqui que configuramos a ligação à base de dados.
     */
    public function boot(): void
    {
        // Condição: não é um comando artisan E o sistema operativo é Linux
        // → app()->runningInConsole() = false durante pedidos HTTP
        // → PHP_OS_FAMILY === 'Linux' = true no Railway e no Android
        //
        // NOTA: O Android também é Linux, mas não tem pdo_mysql.
        // Por isso, a app no Android dá erro ao tentar ligar ao MySQL.
        // Para resolver definitivamente, seria necessário implementar
        // uma API REST e o Android usar SQLite local com sincronização.
        if (app()->runningInConsole() === false && PHP_OS_FAMILY === 'Linux') {
            config(['database.default' => 'mysql']); // força mysql em Linux
        }
    }
}
