<?php

namespace App\Livewire;

use App\Models\Todo;
use App\Models\TodoShare;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TodoEdit extends Component
{
    public Todo $todo;

    // Campos de edição
    public string $task          = '';
    public string $description   = '';
    public bool   $is_recurring  = false;
    public array  $recurring_days = [];

    // Partilha
    public string $shareEmail   = '';
    public string $shareMessage = '';
    public string $shareError   = '';

    public array $diasSemana = [
        1 => 'Seg',
        2 => 'Ter',
        3 => 'Qua',
        4 => 'Qui',
        5 => 'Sex',
        6 => 'Sáb',
        7 => 'Dom',
    ];

    public function mount(int $id): void
    {
        $this->todo = Todo::with('sharedWithUsers')->findOrFail($id);

        // Só o dono pode editar
        if ($this->todo->user_id !== Auth::id()) {
            abort(403);
        }

        $this->task           = $this->todo->task;
        $this->description    = $this->todo->description ?? '';
        $this->is_recurring   = $this->todo->is_recurring;
        $this->recurring_days = $this->todo->getRecurringDaysArray();
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

    public function save(): void
    {
        $this->validate([
            'task'        => 'required|min:3',
            'description' => 'nullable|string|max:500',
        ]);

        $this->todo->update([
            'task'           => $this->task,
            'description'    => $this->description,
            'is_recurring'   => $this->is_recurring,
            'recurring_days' => $this->is_recurring && count($this->recurring_days) > 0
                ? implode(',', $this->recurring_days)
                : null,
        ]);

        session()->flash('success', 'Todo actualizado!');
        $this->redirect('/');
    }

    public function addShare(): void
    {
        $this->shareError   = '';
        $this->shareMessage = '';

        if (empty(trim($this->shareEmail))) {
            $this->shareError = 'Introduz um email.';
            return;
        }

        $user = User::where('email', trim($this->shareEmail))->first();

        if (!$user) {
            $this->shareError = 'Utilizador não encontrado.';
            return;
        }

        if ($user->id === Auth::id()) {
            $this->shareError = 'Não podes partilhar contigo próprio.';
            return;
        }

        $exists = TodoShare::where('todo_id', $this->todo->id)
            ->where('shared_with_user_id', $user->id)
            ->exists();

        if ($exists) {
            $this->shareError = 'Já partilhado com ' . $user->name . '.';
            return;
        }

        TodoShare::create([
            'todo_id'             => $this->todo->id,
            'shared_with_user_id' => $user->id,
        ]);

        $this->shareMessage = 'Partilhado com ' . $user->name . '!';
        $this->shareEmail   = '';

        // Recarrega as partilhas
        $this->todo->load('sharedWithUsers');
    }

    public function removeShare(int $userId): void
    {
        TodoShare::where('todo_id', $this->todo->id)
            ->where('shared_with_user_id', $userId)
            ->delete();

        $this->todo->load('sharedWithUsers');
    }

    public function render()
    {
        return view('livewire.todo-edit')
            ->layout('components.layouts.app');
    }
}
