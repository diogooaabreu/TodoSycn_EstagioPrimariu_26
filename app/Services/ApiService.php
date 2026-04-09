<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class ApiService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.api.url');
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . Session::get('api_token'),
            'Accept'        => 'application/json',
        ];
    }

    // Auth
    public function login(string $email, string $password)
    {
        return Http::post("{$this->baseUrl}/api/login", compact('email', 'password'));
    }

    public function register(string $name, string $email, string $password)
    {
        return Http::post("{$this->baseUrl}/api/register", compact('name', 'email', 'password'));
    }

    // Todos
    public function getTodos()
    {
        return Http::withHeaders($this->headers())->get("{$this->baseUrl}/api/todos");
    }

    public function createTodo(array $data)
    {
        return Http::withHeaders($this->headers())->post("{$this->baseUrl}/api/todos", $data);
    }

    public function updateTodo(int $id, array $data)
    {
        return Http::withHeaders($this->headers())->put("{$this->baseUrl}/api/todos/{$id}", $data);
    }

    public function deleteTodo(int $id)
    {
        return Http::withHeaders($this->headers())->delete("{$this->baseUrl}/api/todos/{$id}");
    }

    public function toggleTodo(int $id)
    {
        return Http::withHeaders($this->headers())->post("{$this->baseUrl}/api/todos/{$id}/toggle");
    }

    public function shareTodo(int $id, string $email)
    {
        return Http::withHeaders($this->headers())->post("{$this->baseUrl}/api/todos/{$id}/share", ['email' => $email]);
    }

    public function removeShare(int $id, int $userId)
    {
        return Http::withHeaders($this->headers())->delete("{$this->baseUrl}/api/todos/{$id}/share/{$userId}");
    }

    public function getTodoDetail(int $id)
    {
        return Http::withHeaders($this->headers())->get("{$this->baseUrl}/api/todos/{$id}");
    }
    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout(15)->withHeaders([
            'Authorization' => 'Bearer ' . session('api_token'),
            'Accept'        => 'application/json',
        ]);
    }
}
