<?php

use App\Models\Desguace;
use App\Models\User;
use App\Services\AdministracionDesguaces;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Crea o actualiza roles y permisos sin tocar usuarios ni datos. Es seguro repetirlo.
// Ejecutar tras desplegar esta version: php artisan scrapex:sincronizar-permisos
Artisan::command('scrapex:sincronizar-permisos', function () {
    $this->call('db:seed', ['--class' => PermissionSeeder::class, '--force' => true]);
    $this->call('db:seed', ['--class' => RoleSeeder::class, '--force' => true]);
    $this->call('db:seed', ['--class' => RolePermissionSeeder::class, '--force' => true]);
    $this->info('Roles y permisos sincronizados. Ningún usuario ha sido modificado.');

    return 0;
})->purpose('Sincroniza roles y permisos (no modifica usuarios)');

// Habilita al PRIMER administrador de plataforma. Solo desde la consola del servidor,
// nunca desde la API. Usa una cuenta EXISTENTE que no pertenezca a ningun desguace.
Artisan::command('scrapex:habilitar-plataforma {email : Correo de una cuenta ya registrada}', function (AdministracionDesguaces $administracion) {
    if (! Role::where('name', RoleSeeder::PLATAFORMA)->where('guard_name', 'web')->exists()) {
        $this->error('Los roles no existen. Ejecuta antes: php artisan scrapex:sincronizar-permisos');

        return 1;
    }

    $usuario = User::where('email', $this->argument('email'))->first();

    if (! $usuario) {
        $this->error('No existe ninguna cuenta con ese correo. Regístrate primero en la aplicación.');

        return 1;
    }

    try {
        $administracion->habilitarPlataforma($usuario);
    } catch (DomainException $e) {
        $this->error($e->getMessage());

        return 1;
    }

    $this->info("La cuenta {$usuario->email} es ahora administrador de plataforma.");

    return 0;
})->purpose('Habilita una cuenta existente como administrador de plataforma');

// Asigna sede y rol a una cuenta EXISTENTE (p. ej. las creadas antes de esta version).
// Nunca se hace de forma automatica: el operador decide cada caso.
Artisan::command('scrapex:asignar-usuario {email : Correo de la cuenta} {id_desguace : Id del desguace} {rol : administrador_desguace, gerente u operario}', function (AdministracionDesguaces $administracion) {
    $rol = $this->argument('rol');

    if (! in_array($rol, RoleSeeder::ROLES_DESGUACE, true)) {
        $this->error('Rol no válido. Usa: '.implode(', ', RoleSeeder::ROLES_DESGUACE));

        return 1;
    }

    if (! Role::where('name', $rol)->where('guard_name', 'web')->exists()) {
        $this->error('Los roles no existen. Ejecuta antes: php artisan scrapex:sincronizar-permisos');

        return 1;
    }

    $usuario = User::where('email', $this->argument('email'))->first();
    $desguace = Desguace::find($this->argument('id_desguace'));

    if (! $usuario || ! $desguace) {
        $this->error('No existe la cuenta o el desguace indicados.');

        return 1;
    }

    try {
        if ($rol === RoleSeeder::ADMIN_DESGUACE) {
            $administracion->designarAdministrador($desguace, $usuario);
        } else {
            if ($usuario->esAdministradorDesguace()) {
                throw new DomainException('La cuenta es administrador de un desguace. Sustitúyela con el administrador de plataforma antes de cambiarle el rol.');
            }
            $administracion->asignarDesguace($usuario, $desguace);
            $usuario->syncRoles([$rol]);
        }
    } catch (DomainException $e) {
        $this->error($e->getMessage());

        return 1;
    }

    $this->info("La cuenta {$usuario->email} queda como {$rol} del desguace {$desguace->id_desguace} ({$desguace->nombre}).");

    return 0;
})->purpose('Asigna sede y rol a una cuenta existente');
