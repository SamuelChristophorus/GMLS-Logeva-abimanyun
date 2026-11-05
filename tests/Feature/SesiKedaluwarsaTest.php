<?php

namespace Tests\Feature;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Form yang sesinya habis (419) dikembalikan ke halaman asal dengan pesan, bukan halaman buntu. */
class SesiKedaluwarsaTest extends TestCase
{
    public function test_sesi_habis_kembali_ke_form_dengan_pesan(): void
    {
        Route::middleware('web')->post('/_uji-sesi-habis', fn () => throw new TokenMismatchException('CSRF token mismatch.'));

        $this->from('/login')
            ->post('/_uji-sesi-habis', ['email' => 'admin@contoh.id', 'password' => 'rahasia'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('sesi')
            ->assertSessionHasInput('email', 'admin@contoh.id')
            ->assertSessionMissing('_old_input.password');
    }
}
