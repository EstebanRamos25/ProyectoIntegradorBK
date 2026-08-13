<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Orchid\Platform\Models\Role;

class ClientAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors(['email' => 'Las credenciales no son válidas.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $defaultRedirect = ($user && method_exists($user, 'inRole') && $user->inRole('client'))
            ? route('three.menu')
            : route(config('platform.index'));

        return redirect()->intended($defaultRedirect);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // Reglas simples para clientes (evita que el registro falle por complejidad excesiva)
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Rol por defecto para clientes
        $clientRole = Role::query()->firstOrCreate(
            ['slug' => 'client'],
            ['name' => 'Cliente', 'permissions' => []]
        );
        $user->addRole($clientRole);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('three.menu');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
