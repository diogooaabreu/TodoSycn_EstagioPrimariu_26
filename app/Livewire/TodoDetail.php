<?php
/**
 * LIVEWIRE: TodoDetail
 * Atualizado para consumir a API REST (dados em array, não Model)
 */
namespace App\Livewire;

use App\Services\ApiService;
use Livewire\Component;

class TodoDetail extends Component
{
    public array $todo      = [];
    public array $sevenDays = [];
    public array $history   = [];
    public array $stats     = [];

    public function mount(int $id): void
    {
        $api      = new ApiService();
        $response = $api->getTodoDetail($id);

        if ($response->status() === 403) abort(403);
        if ($response->status() === 404) abort(404);

        $data = $response->json();

        $this->todo      = $data['todo']       ?? [];
        $this->sevenDays = $data['seven_days'] ?? [];
        $this->stats     = $data['stats']      ?? [];
        $this->history   = $data['history']    ?? [];
    }

    public function render()
    {
        return view('livewire.todo-details', [
            'todo'      => $this->todo,
            'sevenDays' => $this->sevenDays,
            'history'   => $this->history,
            'stats'     => $this->stats,
        ])->layout('components.layouts.app');
    }
}
