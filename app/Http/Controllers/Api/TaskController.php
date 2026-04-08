<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todo;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    // GET /api/todos — lista as tarefas do utilizador autenticado
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->todos()->latest()->get()
        );
    }

    // POST /api/todos — cria nova tarefa
    public function store(Request $request)
    {
        $data = $request->validate([
            'task'        => 'required|string|max:255',  // ← era 'title'
            'description' => 'nullable|string',
        ]);

        $todo = $request->user()->todos()->create($data);

        return response()->json($todo, 201);
    }

    // PUT /api/todos/{todo} — atualiza (ex: marcar como feita)
    public function update(Request $request, Todo $todo)
    {
        if ($todo->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $todo->update($request->validate([
            'task'        => 'sometimes|string|max:255',  // ← era 'title'
            'description' => 'nullable|string',
            'is_completed'=> 'sometimes|boolean',         // ← era 'completed'
            'is_recurring'=> 'sometimes|boolean',         // ← era campo inexistente
        ]));

        return response()->json($todo);
    }

    // DELETE /api/todos/{todo} — apaga tarefa
    public function destroy(Request $request, Todo $todo)
    {
        if ($todo->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        $todo->delete();

        return response()->json(null, 204);
    }
}
