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

use Illuminate\Support\Facades\Auth;
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
        // Valida os campos segundo $rules
        // Se falhar: para a execução e os erros aparecem na view com @error('campo')
        $this->validate();

        // Auth::attempt() faz:
        // 1. SELECT na tabela users pelo email
        // 2. Compara a password com bcrypt
        // 3. Se correcto: cria a sessão e devolve true
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            // Gera novo ID de sessão — segurança contra Session Fixation
            // (ataque onde o atacante fixa o ID antes do login)
            session()->regenerate();

            // Redireciona para a lista de tarefas
            return redirect('/');
        }

        // Credenciais erradas: adiciona erro de validação no campo email
        // Usamos 'email' em vez de 'password' para não revelar se o email existe
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
