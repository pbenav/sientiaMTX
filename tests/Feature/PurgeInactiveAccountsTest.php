<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Team;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\InactiveUserWarningMail;

class PurgeInactiveAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Habilitar la purga en los Settings
        Setting::set('purge_inactive_enabled', true);
        Setting::set('purge_inactive_days', 30);
        Setting::set('purge_warning_days', 5);
        Mail::fake();
    }

    public function test_purge_inactive_accounts_handles_null_last_login_using_created_at()
    {
        // 1. Crear un usuario inactivo con last_login_at nulo pero creado hace 35 días
        $inactiveNullLoginUser = User::factory()->create([
            'is_admin' => false,
            'last_login_at' => null,
            'created_at' => Carbon::now()->subDays(35),
            'inactive_warning_sent_at' => null,
        ]);

        // Crear un equipo perteneciente a este usuario
        $team = Team::create([
            'name' => 'Team Inactive',
            'slug' => 'team-inactive',
            'created_by_id' => $inactiveNullLoginUser->id,
            'settings' => ['has_appointments' => true],
        ]);

        // 2. Crear un usuario activo (creado hace 2 días, last_login_at nulo)
        $activeUser = User::factory()->create([
            'is_admin' => false,
            'last_login_at' => null,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        // 3. Crear un administrador (creado hace 40 días, last_login_at nulo)
        $adminUser = User::factory()->create([
            'is_admin' => true,
            'last_login_at' => null,
            'created_at' => Carbon::now()->subDays(40),
        ]);

        // Ejecutar el comando para enviar avisos
        $this->artisan('app:purge-inactive-accounts')
            ->assertSuccessful();

        // Verificar que se envió el email de aviso al usuario inactivo con login nulo
        Mail::assertSent(InactiveUserWarningMail::class, function ($mail) use ($inactiveNullLoginUser) {
            return $mail->hasTo($inactiveNullLoginUser->email);
        });

        // Verificar que no se envió a los usuarios activos ni admins
        Mail::assertNotSent(InactiveUserWarningMail::class, function ($mail) use ($activeUser, $adminUser) {
            return $mail->hasTo($activeUser->email) || $mail->hasTo($adminUser->email);
        });

        // Refrescar el usuario para comprobar que se guardó la fecha del aviso
        $inactiveNullLoginUser->refresh();
        $this->assertNotNull($inactiveNullLoginUser->inactive_warning_sent_at);

        // Simulamos que el aviso se envió hace 6 días (superior al límite de 5 días de grace period)
        $inactiveNullLoginUser->inactive_warning_sent_at = Carbon::now()->subDays(6);
        $inactiveNullLoginUser->save();

        $this->artisan('app:purge-inactive-accounts')
            ->assertSuccessful();

        // Verificar que el usuario inactivo fue eliminado
        $this->assertDatabaseMissing('users', ['id' => $inactiveNullLoginUser->id]);

        // Verificar que el equipo propiedad de dicho usuario fue eliminado en cascada
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);

        // Verificar que los usuarios activos y admins siguen existiendo
        $this->assertDatabaseHas('users', ['id' => $activeUser->id]);
        $this->assertDatabaseHas('users', ['id' => $adminUser->id]);
    }
}
