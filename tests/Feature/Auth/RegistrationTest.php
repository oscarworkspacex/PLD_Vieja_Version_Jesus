<?php

use App\Models\User;

/*
 * El registro público está CERRADO por defecto (FORTIFY_REGISTRATION_ENABLED):
 * las cuentas las crea root desde la gestión de usuarios, y las rutas de admin
 * solo exigen sesión, así que una cuenta auto-creada vería todo.
 *
 * Con la característica desactivada, Fortify ni siquiera registra las rutas,
 * así que aquí se usan URLs literales: `route('register')` lanzaría excepción
 * por no existir el nombre, que es justamente lo que se quiere demostrar.
 */

test('la pantalla de registro no está disponible', function () {
    $this->get('/register')->assertNotFound();
});

test('nadie puede registrarse por su cuenta', function () {
    $antes = User::count();

    $this->post('/register', [
        'name' => 'Intruso',
        'email' => 'intruso@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
    expect(User::count())->toBe($antes);
});

test('la ruta de registro no existe en la tabla de rutas', function () {
    expect(app('router')->has('register'))->toBeFalse();
});
