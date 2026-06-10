<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div title="Usuarios">
    <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">
            <img src="https://tailwindcss.com/plus-assets/img/logos/mark.svg?color=indigo&shade=600" alt="Your Company" class="mx-auto h-10 w-auto" />
            <h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-gray-900">Inicia sesión</h2>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
            <form action="#" method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm/6 font-medium text-gray-900">Correo Electrónico</label>
                    <div class="mt-2">
                        <input 
                            id="email"
                            type="email"
                            name="email"
                            placeholder="Correo Electrónico"
                            required autocomplete="email"
                            tabindex="1"
                            value="{{ old('email') }}"
                            class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                        />
                    </div>
                </div>
                <x-input-error field="email" />

                <div>
                    <div class="flex items-center justify-between">
                    <label for="password" class="block text-sm/6 font-medium text-gray-900">Contraseña</label>
                    <div class="text-sm">
                        <a href="#" class="font-semibold text-indigo-600 hover:text-indigo-500">¿Olvidaste tu Contraseña?</a>
                    </div>
                    </div>
                    <div class="mt-2">
                        <input 
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Contraseña"
                            required autocomplete="current-password"
                            tabindex="2"
                            class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6" 
                        />
                    </div>
                </div>
                <x-input-error field="password" />

                <div>
                    <button type="submit" class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Sign in</button>
                </div>
            </form>
        </div>
    </div>
</div>