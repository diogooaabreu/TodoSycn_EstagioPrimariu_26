<?php
/**
 * ============================================================
 * MODEL: TodoCompletion
 * ============================================================
 * Regista cada vez que uma tarefa foi marcada como concluída
 * por um utilizador numa data específica.
 *
 * Porquê existe separado do campo is_completed?
 * 1. Guarda histórico (is_completed só guarda o estado actual)
 * 2. Cada utilizador (dono + partilhados) tem o seu próprio estado
 * 3. Permite que um utilizador desmarque sem afectar os outros
 *
 * Tabela na BD: todo_completions
 * Constraint: UNIQUE(todo_id, user_id, completed_at)
 *   → impede marcar a mesma tarefa duas vezes no mesmo dia
 * ============================================================
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TodoCompletion extends Model
{
    protected $fillable = [
        'todo_id',       // qual tarefa foi concluída
        'user_id',       // quem a concluiu
        'completed_at',  // quando foi concluída
    ];

    /**
     * Cast de 'completed_at' para objecto date.
     * Permite usar métodos como $completion->completed_at->format('d/m/Y')
     * ou ->isToday() directamente.
     */
    protected $casts = [
        'completed_at' => 'date',
    ];

    /** A conclusão pertence a uma tarefa */
    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    /** A conclusão pertence a um utilizador */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
