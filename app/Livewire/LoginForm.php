<?php
/**
 * ============================================================
 * LIVEWIRE: LoginForm
 * ============================================================
 * Componente Livewire que gere o ecrã de login.
 *
 * Livewire é uma biblioteca que permite criar interfaces
 * reactivas (sem recarregar a página) usando PHP puro.
 * Quando o utilizador carrega "Entrar", o Livewire envia
 * um pedido AJAX e actualiza só a parte que mudou.
 *
 * View associada: resources/views/livewire/login-form.blade.php
 * Rota: GET /login
 * ============================================================
 */
namespace App\Livewire;

use Livewire\Component;

class LoginForm extends Component
{
    // Propriedades públicas = reactivas
    // wire:model="email" na view mantém estas variáveis sincronizadas
    // com o que o utilizador escreve no input
    public $email    = '';
    public $password = '';

    /**
     * Regras de validação dos campos.
     * Chamadas automaticamente por $this->validate()
     * antes de tentar autenticar.
     */
    protected $rules = [
        'email'    => 'required|email',   // obrigatório e formato email válido
        'password' => 'required|min:6',   // obrigatório e mínimo 6 caracteres
    ];

    /**
     * Metodo chamado quando o utilizador submete o formulário.
     * wire:submit.prevent="login" na view chama este metodo.
     */
    public function login()
    {
        $this->validate();

        $api      = new \App\Services\ApiService();
        $response = $api->login($this->email, $this->password);

        if ($response->successful()) {
            session(['api_token' => $response->json('token')]);
            session(['api_user'  => $response->json('user')]);
            return redirect('/');
        }

        $this->addError('email', 'Email ou password incorrectos.');
    }

    /**
     * Metodo chamado pelo Livewire para renderizar o componente.
     * ->layout() define qual layout HTML base usar.
     */
    public function render()
    {
        return view('livewire.login-form')
            ->layout('components.layouts.app');
    }
}
