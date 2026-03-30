<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class RegisterForm extends Component
{
    public $name     = '';
    public $email    = '';
    public $password = '';

    protected $rules = [
        'name'     => 'required|min:2|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|min:6',
    ];

    protected $messages = [
        'email.unique' => 'Este email já está registado.',
    ];

    public function register()
    {
        $this->validate();

        $user = User::create([
            'name'     => $this->name,
            'email'    => $this->email,
            'password' => Hash::make($this->password),
        ]);

        Auth::login($user);
        session()->regenerate();

        return redirect('/');
    }

    public function render()
    {
        return view('livewire.register-form')
            ->layout('components.layouts.app');
    }
}
