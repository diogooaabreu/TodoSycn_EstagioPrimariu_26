<?php
/**
 * ============================================================
 * MODEL: Toodo
 * ============================================================
 * Representa uma tarefa na aplicação.
 *
 * Tabela na BD: todos
 * Campos principais:
 *   - task           → título da tarefa
 *   - description    → descrição opcional
 *   - is_completed   → se a tarefa está concluída (para tarefas normais)
 *   - is_recurring   → se a tarefa se repete
 *   - recurring_days → dias da semana em que repete (ex: "1,3,5")
 *
 * Relações:
 *   - BelongsTo(User)           → pertence a um utilizador (o dono)
 *   - HasMany(TodoCompletion)   → tem muitas conclusões (histórico)
 *   - HasMany(TodoShare)        → tem muitas partilhas
 *   - BelongsToMany(User)       → utilizadores com quem foi partilhado
 * ============================================================
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Todo extends Model
{
    use HasFactory;

    /** @var string Nome da tabela na base de dados */
    protected $table = 'todos';

    /**
     * Campos que podem ser preenchidos em massa.
     * Qualquer campo fora desta lista é ignorado em Toodo::create([...])
     */
    protected $fillable = [
        'user_id',        // dono da tarefa
        'task',           // título
        'description',    // descrição opcional
        'is_completed',   // concluída? (para tarefas normais)
        'is_recurring',   // repete?
        'recurring_days', // "1,3,5" = seg, qua, sex
    ];

    /**
     * Conversão automática de tipos.
     * Sem isto, is_completed devolveria "0" ou "1" (string) em vez de false/true (boolean)
     * o que causaria bugs: if ("0") é true em PHP!
     */
    protected $casts = [
        'is_completed' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    // ============================================================
    // RELAÇÕES
    // ============================================================

    /**
     * Relação inversa: esta tarefa pertence a um utilizador (o dono).
     * Uso: $todo->user → devolve o objecto User do dono
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relação: histórico de todas as conclusões desta tarefa.
     * Cada vez que alguém marca como feito, cria uma linha em todo_completions.
     * Uso: $todo->completions → devolve toda a colecção de conclusões
     */
    public function completions(): HasMany
    {
        return $this->hasMany(TodoCompletion::class);
    }

    /**
     * Relação: registos de partilha desta tarefa.
     * Uso: $todo->shares → devolve os registos na tabela todo_shares
     */
    public function shares(): HasMany
    {
        return $this->hasMany(TodoShare::class);
    }

    /**
     * Relação muitos-para-muitos: utilizadores com quem esta tarefa foi partilhada.
     * Uso: $todo->sharedWithUsers → devolve colecção de User
     */
    public function sharedWithUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'todo_shares',           // tabela pivot
            'todo_id',               // coluna que referencia esta tarefa
            'shared_with_user_id'    // coluna que referencia o utilizador
        );
    }

    // ============================================================
    // MÉTODOS DE LÓGICA DE NEGÓCIO
    // ============================================================

    /**
     * Verifica se um utilizador específico completou esta tarefa hoje.
     *
     * Usado para tarefas repetidas e para tarefas partilhadas,
     * onde cada utilizador tem o seu próprio estado independente.
     *
     * @param int $userId ID do utilizador a verificar
     * @return bool true se completou hoje, false se não
     */
    public function isCompletedTodayByUser(int $userId): bool
    {
        return $this->completions()
            ->where('user_id', $userId)          // só deste utilizador
            ->whereDate('completed_at', today()) // só de hoje
            ->exists();                          // devolve true/false sem carregar os dados
    }

    /**
     * Devolve os últimos 7 dias com o estado de conclusão para um utilizador.
     *
     * Resultado: array com 7 itens, do dia mais antigo para hoje.
     * Cada item tem:
     *   - date  → data em formato "2026-03-30"
     *   - label → dia abreviado em português ("seg", "ter", etc.)
     *   - done  → true se foi feito nesse dia
     *   - today → true se é o dia de hoje
     *
     * @param int $userId ID do utilizador
     * @return array
     */
    public function lastSevenDays(int $userId): array
    {
        // range(6, 0) gera [6, 5, 4, 3, 2, 1, 0]
        // Para cada número, calcula hoje - N dias
        // Resultado: 7 dias do mais antigo para o mais recente (esq → dir)
        return collect(range(6, 0))->map(function ($i) use ($userId) {
            $date = today()->subDays($i); // hoje menos i dias

            // Verifica se existe uma conclusão neste dia para este utilizador
            $done = $this->completions()
                ->where('user_id', $userId)
                ->whereDate('completed_at', $date)
                ->exists();

            return [
                'date'  => $date->toDateString(),
                'label' => $date->locale('pt')->isoFormat('ddd'), // "seg", "ter", etc.
                'done'  => $done,
                'today' => $date->isToday(), // para destaque visual do dia actual
            ];
        })->toArray();
    }

    /**
     * Devolve o histórico completo de conclusões agrupado por mês.
     *
     * Usado no ecrã de detalhe para mostrar o histórico completo.
     * Resultado: array de meses com o total e os dias específicos.
     *
     * @param int $userId ID do utilizador
     * @return array
     */
    public function fullHistory(int $userId): array
    {
        return $this->completions()
            ->where('user_id', $userId)
            ->orderBy('completed_at', 'desc') // mais recente primeiro
            ->get()
            // Agrupa por "2026-03", "2026-02", etc.
            ->groupBy(fn($c) => $c->completed_at->format('Y-m'))
            ->map(fn($group, $month) => [
                // Converte "2026-03" para "Março 2026" em português
                'mes'   => \Carbon\Carbon::parse($month . '-01')->locale('pt')->isoFormat('MMMM YYYY'),
                'total' => $group->count(),
                // Array com só os dias numéricos: [1, 5, 12, 15, ...]
                'dias'  => $group->map(fn($c) => $c->completed_at->format('d'))->toArray(),
            ])
            ->values() // reindexa o array (remove as chaves "2026-03")
            ->toArray();
    }

    /**
     * Calcula estatísticas de conclusão para um utilizador.
     *
     * @param int $userId ID do utilizador
     * @return array com total, esta_semana e este_mes
     */
    public function statsForUser(int $userId): array
    {
        // Total de vezes concluído (todos os tempos)
        $totalFeito = $this->completions()
            ->where('user_id', $userId)
            ->count();

        // Vezes concluído na semana actual (Segunda a Domingo)
        $estaSemana = $this->completions()
            ->where('user_id', $userId)
            ->whereBetween('completed_at', [
                now()->startOfWeek(), // Segunda-feira desta semana
                now()->endOfWeek(),   // Domingo desta semana
            ])
            ->count();

        // Vezes concluído no mês actual
        $esteMes = $this->completions()
            ->where('user_id', $userId)
            ->whereMonth('completed_at', now()->month) // só este mês
            ->whereYear('completed_at', now()->year)   // e este ano
            ->count();

        return [
            'total'       => $totalFeito,
            'esta_semana' => $estaSemana,
            'este_mes'    => $esteMes,
        ];
    }

    /**
     * Converte a string de dias repetidos para um array de inteiros.
     *
     * Exemplo: "1,3,5" → [1, 3, 5]
     * (1=Segunda, 2=Terça, 3=Quarta, 4=Quinta, 5=Sexta, 6=Sábado, 7=Domingo)
     *
     * @return array<int>
     */
    public function getRecurringDaysArray(): array
    {
        if (!$this->recurring_days) {
            return []; // sem dias definidos, devolve array vazio
        }

        // explode(',', "1,3,5") → ["1", "3", "5"]
        // array_map('intval', ...) → [1, 3, 5] (converte strings para inteiros)
        return array_map('intval', explode(',', $this->recurring_days));
    }
}
