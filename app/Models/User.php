<?php
/**
 * ============================================================
 * MODEL: User
 * ============================================================
 * Representa um utilizador registado na aplicação.
 * O Laravel usa este modelo para toda a autenticação.
 *
 * Tabela na BD: users
 * Relações:
 *   - hasMany(Toodo)       → um utilizador tem muitas tarefas
 *   - BelongsToMany(Toodo) → tarefas partilhadas com este utilizador
 * ============================================================
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    // HasFactory     → permite criar utilizadores falsos em testes
    // Notifiable     → permite enviar notificações (email, push, etc.)
    use HasFactory, Notifiable;

    /**
     * Campos que podem ser preenchidos em massa (Mass Assignment Protection).
     * Segurança: sem esta lista, qualquer campo enviado num formulário
     * poderia ser inserido directamente na base de dados.
     */
    protected $fillable = ['name', 'email', 'password'];
    /**
     * Campos ocultos quando o modelo é convertido para JSON.
     * A password nunca deve aparecer em respostas da API ou logs.
     */
    protected $hidden   = ['password', 'remember_token'// token de sessão persistente
    ];

           /**
            * Conversão automática de tipos entre PHP e base de dados.
            * 'hashed' → a password é encriptada automaticamente com bcrypt
            *           quando atribuída: $user->password = '123' fica guardado
            *           como '$2y$12$...' sem precisar de chamar Hash::make()
            */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime', // converte string da BD para objecto Carbon
            'password'          => 'hashed',   // encripta automaticamente ao guardar
        ];
    }

       /**
        * Relação: um utilizador tem muitas tarefas.
        * Uso: $user->todos → devolve todas as tarefas do utilizador
        */
    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

       /**
        * Relação: tarefas que foram partilhadas COM este utilizador (por outros).
        * Usa a tabela pivot 'todo_shares' para ligar utilizadores a tarefas.
        * Uso: $user->sharedTodos → devolve as tarefas que outros partilharam com ele
        */
    public function sharedTodos(): BelongsToMany
    {
        return $this->belongsToMany(
            Todo::class,
            'todo_shares',
            'shared_with_user_id',
            'todo_id'
        );
    }
}
