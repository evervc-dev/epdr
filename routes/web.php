<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::view('/', 'welcome')->name('home');

Route::livewire('/auth/login', 'pages::auth.login')->name('login');