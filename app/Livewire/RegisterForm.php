<?php
/**
 * ============================================================
 * LIVEWIRE: RegisterForm
 * ============================================================
 * Componente que gere o ecrã de registo de nova conta.
 *
 * View associada: resources/views/livewire/register-form.blade.php
 * Rota: GET /register
 * ============================================================
 */
namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class RegisterForm extends Component
{
    // Campos do formulário — sincronizados com os inputs via wire:model
    public $name     = '';
    public $email    = '';
    public $password = '';

    /**
     * Regras de validação.
     * unique:users,email → verifica na tabela 'users', coluna 'email'
     * se já existe esse email. Se existir, falha com $messages['email.unique'].
     */
    protected $rules = [
        'name'     => 'required|min:2|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|min:6',
    ];

    /**
     * Mensagens de erro personalizadas.
     * Formato: 'campo.regra' → mensagem
     * Sem isto, apareceria a mensagem em inglês padrão do Laravel.
     */
    protected $messages = [
        'email.unique' => 'Este email já está registado.',
    ];

    /**
     * Cria a conta e faz login automático.
     * wire:submit.prevent="register" na view chama este método.
     */
    public function register()
    {
        // Valida antes de criar qualquer coisa na BD
        $this->validate();

        // Cria o utilizador na BD
        // Hash::make() encripta a password com bcrypt
        // (mesmo que o cast 'hashed' faça isto automaticamente,
        // é boa prática ser explícito)
        $user = User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => Hash::make($this->password),
        ]);

        // Faz login imediatamente após criar a conta
        // O utilizador não precisa de ir ao ecrã de login
        Auth::login($user);
        session()->regenerate(); // nova sessão por segurança

        return redirect('/');
    }

    public function render()
    {
        return view('livewire.register-form')
            ->layout('components.layouts.app');
    }
}
