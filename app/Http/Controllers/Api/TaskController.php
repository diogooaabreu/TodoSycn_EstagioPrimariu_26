<?php

/**
 * ============================================================
 * CONTROLLER: TaskController
 * ============================================================
 * Gere todas as operações sobre tarefas via API REST.
 *
 * PROBLEMA DO CÓDIGO ORIGINAL:
 * - Usava modelo 'Task' que não existe (é 'Todo')
 * - Task::all() devolvia tarefas de TODOS os utilizadores
 * - Sem autenticação — qualquer pessoa acedia a tudo
 * - Sem as funcionalidades do projecto (repetição, partilhas)
 *
 * Endpoints:
 * GET    /api/tasks              → listar tarefas próprias + partilhadas
 * POST   /api/tasks              → criar tarefa
 * GET    /api/tasks/{id}         → detalhe de uma tarefa
 * PATCH  /api/tasks/{id}         → actualizar tarefa
 * DELETE /api/tasks/{id}         → eliminar tarefa
 * POST   /api/tasks/{id}/complete   → marcar como feita hoje
 * DELETE /api/tasks/{id}/complete   → desmarcar conclusão de hoje
 * POST   /api/tasks/{id}/share      → partilhar com utilizador
 * DELETE /api/tasks/{id}/share/{uid}→ remover partilha
 *
 * Todos os endpoints requerem autenticação via token Sanctum.
 * ============================================================
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use App\Models\TodoCompletion;
use App\Models\TodoShare;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Lista as tarefas do utilizador autenticado.
     *
     * PROBLEMA ORIGINAL: Task::all() devolvia tarefas de todos os utilizadores.
     * CORRECÇÃO: filtramos por user_id = utilizador autenticado.
     *
     * Devolve:
     * - tarefas_proprias: tarefas criadas pelo utilizador
     * - tarefas_partilhadas: tarefas que outros partilharam com ele
     *
     * GET /api/tasks
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id; // ID do utilizador autenticado pelo token

        // Tarefas próprias — com o histórico semanal calculado
        $proprias = Todo::where('user_id', $userId)
            ->latest()
            ->get()
            ->map(fn($todo) => $this->formatarTarefa($todo, $userId));

        // Tarefas partilhadas com este utilizador
        $partilhadas = Todo::whereHas('shares', function ($q) use ($userId) {
            $q->where('shared_with_user_id', $userId);
        })
            ->with('user') // eager loading do dono para mostrar "de [nome]"
            ->latest()
            ->get()
            ->map(fn($todo) => $this->formatarTarefa($todo, $userId, true));

        return response()->json([
            'tarefas_proprias'    => $proprias,
            'tarefas_partilhadas' => $partilhadas,
        ]);
    }

    /**
     * Cria uma nova tarefa.
     *
     * PROBLEMA ORIGINAL: só guardava o título, sem user_id, sem validação.
     * CORRECÇÃO: associa ao utilizador autenticado, valida e suporta repetição.
     *
     * Recebe: task (obrigatório), description, is_recurring, recurring_days
     * Devolve: a tarefa criada em JSON
     *
     * POST /api/tasks
     */
    public function store(Request $request): JsonResponse
    {
        // Validação dos dados recebidos
        $data = $request->validate([
            'task'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'is_recurring'   => ['nullable', 'boolean'],
            // recurring_days deve ser array de inteiros entre 1 e 7
            'recurring_days' => ['nullable', 'array'],
            'recurring_days.*' => ['integer', 'between:1,7'],
        ]);

        // Cria a tarefa associada ao utilizador autenticado
        $todo = Todo::create([
            'user_id'        => $request->user()->id, // ESSENCIAL — sem isto qualquer um cria tarefas para qualquer um
            'task'           => $data['task'],
            'description'    => $data['description'] ?? null,
            'is_recurring'   => $data['is_recurring'] ?? false,
            // Converte array [1,3,5] para string "1,3,5" para guardar na BD
            'recurring_days' => isset($data['recurring_days']) && count($data['recurring_days']) > 0
                ? implode(',', $data['recurring_days'])
                : null,
        ]);

        return response()->json(
            $this->formatarTarefa($todo, $request->user()->id),
            201 // 201 Created
        );
    }

    /**
     * Devolve o detalhe de uma tarefa específica.
     *
     * Inclui: estatísticas, histórico semanal, histórico completo, partilhas.
     *
     * GET /api/tasks/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $todo   = Todo::with(['user', 'sharedWithUsers', 'completions'])->findOrFail($id);
        $userId = $request->user()->id;

        // Verificação de permissão: só o dono ou partilhados podem ver
        $isOwner  = $todo->user_id === $userId;
        $isShared = $todo->sharedWithUsers->contains('id', $userId);

        if (!$isOwner && !$isShared) {
            return response()->json(['error' => 'Sem permissão para ver esta tarefa.'], 403);
        }

        return response()->json([
            'id'              => $todo->id,
            'task'            => $todo->task,
            'description'     => $todo->description,
            'is_recurring'    => $todo->is_recurring,
            'recurring_days'  => $todo->getRecurringDaysArray(),
            'feito_hoje'      => $todo->isCompletedTodayByUser($userId),
            'ultimos_7_dias'  => $todo->lastSevenDays($userId),
            'historico_completo' => $todo->fullHistory($userId),
            'estatisticas'    => $todo->statsForUser($userId),
            'dono'            => [
                'id'   => $todo->user->id,
                'name' => $todo->user->name,
            ],
            // Lista de utilizadores com quem foi partilhado (só para o dono)
            'partilhado_com'  => $isOwner
                ? $todo->sharedWithUsers->map(fn($u) => [
                    'id'    => $u->id,
                    'name'  => $u->name,
                    'email' => $u->email,
                ])
                : null,
        ]);
    }

    /**
     * Actualiza uma tarefa.
     * Só o dono pode editar.
     *
     * Recebe: qualquer combinação de task, description, is_recurring, recurring_days
     * Devolve: a tarefa actualizada
     *
     * PATCH /api/tasks/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $todo = Todo::findOrFail($id);

        // Verifica se é o dono
        if ($todo->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Sem permissão para editar esta tarefa.'], 403);
        }

        // 'sometimes' = só valida se o campo foi enviado
        $data = $request->validate([
            'task'             => ['sometimes', 'string', 'max:255'],
            'description'      => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_recurring'     => ['sometimes', 'boolean'],
            'recurring_days'   => ['sometimes', 'nullable', 'array'],
            'recurring_days.*' => ['integer', 'between:1,7'],
        ]);

        // Prepara os dados a actualizar
        $toUpdate = [];

        if (isset($data['task']))           $toUpdate['task']        = $data['task'];
        if (isset($data['description']))    $toUpdate['description'] = $data['description'];
        if (isset($data['is_recurring']))   $toUpdate['is_recurring'] = $data['is_recurring'];

        if (isset($data['recurring_days'])) {
            $toUpdate['recurring_days'] = count($data['recurring_days']) > 0
                ? implode(',', $data['recurring_days'])
                : null;
        }

        $todo->update($toUpdate);

        return response()->json(
            $this->formatarTarefa($todo->fresh(), $request->user()->id)
        );
    }

    /**
     * Elimina uma tarefa.
     * Só o dono pode eliminar.
     *
     * DELETE /api/tasks/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $todo = Todo::findOrFail($id);

        if ($todo->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Sem permissão para eliminar esta tarefa.'], 403);
        }

        $todo->delete(); // cascadeOnDelete apaga também completions e shares

        return response()->json(['message' => 'Tarefa eliminada com sucesso.']);
    }

    /**
     * Marca a tarefa como concluída hoje.
     * Funciona para o dono e para utilizadores com quem foi partilhada.
     * Cada utilizador tem o seu próprio estado independente.
     *
     * POST /api/tasks/{id}/complete
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $todo   = Todo::findOrFail($id);
        $userId = $request->user()->id;

        // Verifica permissão
        $isOwner  = $todo->user_id === $userId;
        $isShared = TodoShare::where('todo_id', $id)
            ->where('shared_with_user_id', $userId)
            ->exists();

        if (!$isOwner && !$isShared) {
            return response()->json(['error' => 'Sem permissão.'], 403);
        }

        // Verifica se já foi marcada hoje (a constraint unique na BD também protege)
        $jaFeita = TodoCompletion::where('todo_id', $id)
            ->where('user_id', $userId)
            ->whereDate('completed_at', today())
            ->exists();

        if ($jaFeita) {
            return response()->json(['message' => 'Já marcaste esta tarefa hoje.'], 422);
        }

        // Para tarefas normais do dono, actualiza também o is_completed
        if (!$todo->is_recurring && $isOwner) {
            $todo->update(['is_completed' => true]);
        }

        // Cria o registo de conclusão (para histórico e para partilhados)
        TodoCompletion::create([
            'todo_id'      => $id,
            'user_id'      => $userId,
            'completed_at' => today(),
        ]);

        return response()->json(
            $this->formatarTarefa($todo->fresh(), $userId)
        );
    }

    /**
     * Remove a marcação de hoje (desfazer conclusão).
     *
     * DELETE /api/tasks/{id}/complete
     */
    public function uncomplete(Request $request, int $id): JsonResponse
    {
        $todo   = Todo::findOrFail($id);
        $userId = $request->user()->id;

        // Verifica permissão
        $isOwner  = $todo->user_id === $userId;
        $isShared = TodoShare::where('todo_id', $id)
            ->where('shared_with_user_id', $userId)
            ->exists();

        if (!$isOwner && !$isShared) {
            return response()->json(['error' => 'Sem permissão.'], 403);
        }

        // Apaga a conclusão de hoje
        TodoCompletion::where('todo_id', $id)
            ->where('user_id', $userId)
            ->whereDate('completed_at', today())
            ->delete();

        // Para tarefas normais do dono, reverte o is_completed
        if (!$todo->is_recurring && $isOwner) {
            $todo->update(['is_completed' => false]);
        }

        return response()->json(
            $this->formatarTarefa($todo->fresh(), $userId)
        );
    }

    /**
     * Partilha uma tarefa com outro utilizador pelo email.
     * Só o dono pode partilhar.
     *
     * Recebe: email do utilizador destino
     *
     * POST /api/tasks/{id}/share
     */
    public function share(Request $request, int $id): JsonResponse
    {
        $todo = Todo::findOrFail($id);

        // Só o dono pode partilhar
        if ($todo->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Só o dono pode partilhar esta tarefa.'], 403);
        }

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Procura o utilizador destino pelo email
        $destino = User::where('email', $request->email)->first();

        if (!$destino) {
            return response()->json(['error' => 'Utilizador não encontrado.'], 404);
        }

        if ($destino->id === $request->user()->id) {
            return response()->json(['error' => 'Não podes partilhar contigo próprio.'], 422);
        }

        // Verifica se já foi partilhado
        $jaPartilhado = TodoShare::where('todo_id', $id)
            ->where('shared_with_user_id', $destino->id)
            ->exists();

        if ($jaPartilhado) {
            return response()->json(['error' => 'Já partilhado com este utilizador.'], 422);
        }

        TodoShare::create([
            'todo_id'             => $id,
            'shared_with_user_id' => $destino->id,
        ]);

        return response()->json([
            'message' => 'Tarefa partilhada com ' . $destino->name . '.',
            'utilizador' => [
                'id'    => $destino->id,
                'name'  => $destino->name,
                'email' => $destino->email,
            ],
        ]);
    }

    /**
     * Remove o acesso de um utilizador a esta tarefa.
     * Só o dono pode remover partilhas.
     *
     * DELETE /api/tasks/{id}/share/{userId}
     */
    public function removeShare(Request $request, int $id, int $userId): JsonResponse
    {
        $todo = Todo::findOrFail($id);

        if ($todo->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Sem permissão.'], 403);
        }

        TodoShare::where('todo_id', $id)
            ->where('shared_with_user_id', $userId)
            ->delete();

        return response()->json(['message' => 'Partilha removida.']);
    }

    // ============================================================
    // MÉTODO PRIVADO AUXILIAR
    // ============================================================

    /**
     * Formata uma tarefa para JSON de forma consistente.
     * Usado em vários métodos para não repetir código.
     *
     * @param Todo $todo          A tarefa a formatar
     * @param int  $userId        ID do utilizador para calcular o estado
     * @param bool $incluiDono    Se deve incluir os dados do dono (para partilhadas)
     */
    private function formatarTarefa(Todo $todo, int $userId, bool $incluiDono = false): array
    {
        $dados = [
            'id'             => $todo->id,
            'task'           => $todo->task,
            'description'    => $todo->description,
            'is_recurring'   => $todo->is_recurring,
            'recurring_days' => $todo->getRecurringDaysArray(), // "1,3,5" → [1,3,5]
            'feito_hoje'     => $todo->isCompletedTodayByUser($userId),
            // Histórico semanal: só para tarefas repetidas (para não fazer queries desnecessárias)
            'ultimos_7_dias' => $todo->is_recurring ? $todo->lastSevenDays($userId) : [],
        ];

        // Inclui o nome do dono para tarefas partilhadas
        if ($incluiDono && $todo->relationLoaded('user')) {
            $dados['dono'] = [
                'id'   => $todo->user->id,
                'name' => $todo->user->name,
            ];
        }

        return $dados;
    }
}
