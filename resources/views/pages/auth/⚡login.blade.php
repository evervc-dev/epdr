<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

new class extends Component
{
    public string $email = '';
    public string $password = '';

    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'password.required' => 'La contraseña es requerida.',
        ];
    }

    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ]);
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts.guest')
            ->title('Iniciar Sesión | SIG Centro Escolar Pablo J. Aguirre');
    }
};
?>

<div class="bg-white/95 border border-slate-200 rounded-3xl shadow-xl p-8 sm:p-10 transition-all duration-300">
    <div class="text-center mb-8">
        <div class="mx-auto h-14 w-14 rounded-2xl bg-indigo-600 flex items-center justify-center text-white font-extrabold text-2xl shadow-lg shadow-indigo-100">
            SIG
        </div>
        <h2 class="mt-5 text-2xl font-bold tracking-tight text-slate-900">Centro Escolar "Pablo J. Aguirre"</h2>
        <p class="mt-2 text-sm text-slate-500 font-medium">Sistema de Información Gerencial</p>
    </div>

    <form wire:submit.prevent="login" class="space-y-6">
        <div>
            <label for="email" class="block text-sm font-semibold text-slate-700">Correo Electrónico</label>
            <div class="mt-2">
                <input 
                    id="email"
                    type="email"
                    wire:model="email"
                    placeholder="ejemplo@cepja.edu.sv"
                    required 
                    autocomplete="email"
                    tabindex="1"
                    class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition duration-150" 
                />
            </div>
            <div class="mt-1">
                <x-input-error field="email" />
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-semibold text-slate-700">Contraseña</label>
            </div>
            <div class="mt-2">
                <input 
                    id="password"
                    type="password"
                    wire:model="password"
                    placeholder="••••••••"
                    required 
                    autocomplete="current-password"
                    tabindex="2"
                    class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-slate-900 placeholder:text-slate-400 focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 sm:text-sm transition duration-150" 
                />
            </div>
            <div class="mt-1">
                <x-input-error field="password" />
            </div>
        </div>

        <div>
            <button 
                type="submit" 
                class="flex w-full justify-center rounded-xl bg-indigo-600 hover:bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:shadow transition duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
                Iniciar Sesión
            </button>
        </div>
    </form>
</div>