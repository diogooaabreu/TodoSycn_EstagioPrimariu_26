<?php
/**
 * ============================================================
 * MODEL: TodoShare
 * ============================================================
 * Regista que uma tarefa foi partilhada com um utilizador.
 *
 * Funcionamento simples:
 *   - Criar registo = partilhar a tarefa
 *   - Apagar registo = remover o acesso
 *
 * Tabela na BD: todo_shares
 * Campos:
 *   - todo_id              → qual tarefa foi partilhada
 *   - shared_with_user_id  → com quem foi partilhada
 *
 * Constraint: UNIQUE(todo_id, shared_with_user_id)
 *   → a mesma tarefa não pode ser partilhada duas vezes com o mesmo utilizador
 * ============================================================
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TodoShare extends Model
{
    protected $fillable = [
        'todo_id',
        'shared_with_user_id',
    ];

    /** A partilha pertence a uma tarefa */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    /** A partilha pertence ao utilizador com quem foi partilhado */
    public function sharedWith(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }
}
