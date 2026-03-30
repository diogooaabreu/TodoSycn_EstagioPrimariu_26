<?php

namespace App\Livewire;

use App\Models\Todo;
use Livewire\Component;

class TodoDetail extends Component
{
    public Todo $todo;

    public function mount(int $id): void
    {
        $this->todo = Todo::with(['user', 'sharedWithUsers', 'completions'])
            ->findOrFail($id);

        // Só o dono ou partilhado pode ver
        $userId = auth()->id();
        $isOwner  = $this->todo->user_id === $userId;
        $isShared = $this->todo->sharedWithUsers
            ->contains('id', $userId);

        if (!$isOwner && !$isShared) {
            abort(403);
        }
    }

    public function render()
    {
        $userId    = auth()->id();
        $sevenDays = $this->todo->lastSevenDays($userId);
        $history   = $this->todo->fullHistory($userId);
        $stats     = $this->todo->statsForUser($userId);

        return view('livewire.todo-details', compact('sevenDays', 'history', 'stats'))
            ->layout('components.layouts.app');
    }
}
