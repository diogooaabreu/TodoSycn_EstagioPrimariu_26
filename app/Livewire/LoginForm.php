<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LoginForm extends Component
{
    public $email    = '';
    public $password = '';

    protected $rules = [
        'email'    => 'required|email',
        'password' => 'required|min:6',
    ];

    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            session()->regenerate();
            return redirect('/');
        }

        $this->addError('email', 'Email ou password incorrectos.');
    }

    public function render()
    {
        return view('livewire.login-form')
            ->layout('components.layouts.app');
    }
}
