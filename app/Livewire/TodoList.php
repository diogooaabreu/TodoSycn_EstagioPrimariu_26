<?php

namespace App\Livewire;

use App\Models\Todo;
use App\Models\TodoCompletion;
use App\Models\TodoShare;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TodoList extends Component
{
    public string $task          = '';
    public string $description   = '';
    public bool   $is_recurring  = false;
    public array  $recurring_days = [];
    public bool   $showForm      = false;

    public array $diasSemana = [
        1 => 'Seg',
        2 => 'Ter',
        3 => 'Qua',
        4 => 'Qui',
        5 => 'Sex',
        6 => 'Sáb',
        7 => 'Dom',
    ];

public function addTodo(): void
{
    $this->validate(['task' => 'required|min:3']);

    $data = [
        'user_id'        => Auth::id(),
        'task'           => $this->task,
        'description'    => $this->description,
        'is_recurring'   => $this->is_recurring,
        'recurring_days' => $this->is_recurring && count($this->recurring_days) > 0
            ? implode(',', $this->recurring_days)
            : null,
    ];

    // VERIFICAÇÃO PARA TELEMÓVEL
    if (PHP_OS_FAMILY === 'Linux' && !app()->runningInConsole()) {
        // Envia para o MySQL do Railway via API
        Http::post('https://todosycnestagioprimariu26-production.up.railway.app/api/tasks', $data);
    } else {
        // Uso normal no PC
        Todo::create($data);
    }

    $this->reset(['task', 'description', 'is_recurring', 'recurring_days', 'showForm']);
}

    public function toggleDay(int $day): void
    {
        if (in_array($day, $this->recurring_days)) {
            $this->recurring_days = array_values(
                array_filter($this->recurring_days, fn($d) => $d != $day)
            );
        } else {
            $this->recurring_days[] = $day;
        }
    }

    public function toggleTodo(int $id): void
    {
        $todo     = Todo::findOrFail($id);
        $userId   = Auth::id();
        $isOwner  = $todo->user_id === $userId;
        $isShared = TodoShare::where('todo_id', $id)
            ->where('shared_with_user_id', $userId)
            ->exists();

        if (!$isOwner && !$isShared) return;

        if ($todo->is_recurring) {
            $existing = TodoCompletion::where('todo_id', $id)
                ->where('user_id', $userId)
                ->whereDate('completed_at', today())
                ->first();

            if ($existing) {
                $existing->delete();
            } else {
                TodoCompletion::create([
                    'todo_id'      => $id,
                    'user_id'      => $userId,
                    'completed_at' => today(),
                ]);
            }
        } else {
            if ($isOwner) {
                $todo->update(['is_completed' => !$todo->is_completed]);
            } else {
                // Partilhado: usa completions para não afectar o dono
                $existing = TodoCompletion::where('todo_id', $id)
                    ->where('user_id', $userId)
                    ->whereDate('completed_at', today())
                    ->first();

                if ($existing) {
                    $existing->delete();
                } else {
                    TodoCompletion::create([
                        'todo_id'      => $id,
                        'user_id'      => $userId,
                        'completed_at' => today(),
                    ]);
                }
            }
        }
    }

    public function deleteTodo(int $id): void
    {
        Todo::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail()
            ->delete();
    }

    public function render()
    {
        $userId = Auth::id();
        $baseUrl = 'https://todosycnestagioprimariu26-production.up.railway.app/api';
    
        // SE FOR TELEMÓVEL, BUSCA VIA API
        if (PHP_OS_FAMILY === 'Linux' && !app()->runningInConsole()) {
            $response = Http::get("$baseUrl/tasks?user_id=$userId");
            $myTodos = collect($response->json() ?? []);
        } else {
            // PC: Busca normal na DB
            $myTodos = Todo::where('user_id', $userId)->latest()->get();
        }
    
        // O restante da lógica de map (completions, recurring) deve ser mantido
        $myTodos = $myTodos->map(function ($todo) use ($userId) {
            // ... (mantém o teu código de mapeamento aqui) ...
            return $todo;
        });
    
        // Repete a lógica para sharedTodos se necessário
        $sharedTodos = collect(); // Simplificado para o exemplo
    
        return view('livewire.todo-list', compact('myTodos', 'sharedTodos'))
            ->layout('components.layouts.app');
    }
}
