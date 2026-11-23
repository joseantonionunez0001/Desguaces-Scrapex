<?php

namespace Tests\Feature\Api;

use App\Models\Desguace;
use App\Models\Pieza;
use App\Models\UbicacionVehiculo;
use App\Models\User;
use App\Models\Vehiculo;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\PreparaAutorizacion;
use Tests\TestCase;

/**
 * Acceso por rol y por desguace con DOS sedes de prueba (A y B).
 * Cada peticion se hace como un usuario recien cargado desde la base de datos,
 * igual que ocurre en produccion.
 */
class AccesoPorDesguaceTest extends TestCase
{
    use PreparaAutorizacion, RefreshDatabase;

    private Desguace $sedeA;

    private Desguace $sedeB;

    /** @var array<string, mixed> */
    private array $invA;

    /** @var array<string, mixed> */
    private array $invB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();

        $this->sedeA = $this->crearSede();
        $this->sedeB = $this->crearSede();
        $this->invA = $this->inventarioDe($this->sedeA);
        $this->invB = $this->inventarioDe($this->sedeB);
    }

    // Autentica como el usuario tal y como esta ahora en la base de datos.
    private function como(User $usuario): User
    {
        $this->app['auth']->forgetGuards();
        $usuario = $usuario->fresh();
        Sanctum::actingAs($usuario, ['*']);

        return $usuario;
    }

    private function datosDesguace(string $nombre): array
    {
        return [
            'nombre' => $nombre,
            'direccion' => 'Calle Mayor 1',
            'numero_telefono' => '912345678',
            'ubicación' => 'Polígono Industrial',
        ];
    }

    /** Rutas de ficha de la sede indicada: recurso => id. */
    private function fichas(array $inv, Desguace $sede): array
    {
        return [
            'desguaces' => $sede->id_desguace,
            'modulos' => $inv['modulo']->getKey(),
            'pasillos' => $inv['pasillo']->getKey(),
            'estanterias' => $inv['estanteria']->getKey(),
            'etiquetas' => $inv['etiqueta']->getKey(),
            'patios' => $inv['patio']->getKey(),
            'sectores' => $inv['sector']->getKey(),
            'filas' => $inv['fila']->getKey(),
            'posiciones' => $inv['posicion']->getKey(),
            'niveles' => $inv['nivel']->getKey(),
            'vehiculos' => $inv['vehiculo']->getKey(),
            'ubicaciones-vehiculo' => $inv['ubicacion']->getKey(),
            'categorias' => $inv['categoria']->getKey(),
            'tipo-piezas' => $inv['tipo']->getKey(),
            'piezas' => $inv['pieza']->getKey(),
            'movimientos' => $inv['movimiento']->getKey(),
        ];
    }

    // ---------- Administrador de plataforma ----------

    public function test_la_plataforma_crea_dos_desguaces_y_asigna_a_sus_administradores(): void
    {
        $this->actuarComo(RoleSeeder::PLATAFORMA);

        $idA = $this->postJson('/api/desguaces', $this->datosDesguace('Desguace Alfa'))
            ->assertStatus(201)->json('data.id_desguace');
        $idB = $this->postJson('/api/desguaces', $this->datosDesguace('Desguace Beta'))
            ->assertStatus(201)->json('data.id_desguace');

        // Alta de una cuenta nueva para la sede A.
        $this->putJson("/api/desguaces/{$idA}/administrador", [
            'name' => 'Ana Alfa',
            'email' => 'ana@alfa.test',
            'password' => 'clave-segura-1',
        ])->assertOk()->assertJsonPath('data.id_desguace', $idA);

        // Cuenta existente para la sede B.
        $existente = User::factory()->create(['id_desguace' => null]);
        $this->putJson("/api/desguaces/{$idB}/administrador", ['id_usuario' => $existente->id])
            ->assertOk();

        $ana = User::where('email', 'ana@alfa.test')->firstOrFail();
        $this->assertTrue($ana->hasRole(RoleSeeder::ADMIN_DESGUACE));
        $this->assertSame($idA, $ana->id_desguace);
        $this->assertTrue($existente->fresh()->hasRole(RoleSeeder::ADMIN_DESGUACE));
        $this->assertSame($idB, $existente->fresh()->id_desguace);
    }

    public function test_la_plataforma_puede_sustituir_al_administrador_de_una_sede(): void
    {
        $viejo = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE, $this->sedeA);
        $nuevo = User::factory()->create(['id_desguace' => null]);
        $viejo->createToken('api-token');
        $this->actuarComo(RoleSeeder::PLATAFORMA);

        $this->putJson("/api/desguaces/{$this->sedeA->id_desguace}/administrador", ['id_usuario' => $nuevo->id])
            ->assertOk();

        $this->assertFalse($viejo->fresh()->hasRole(RoleSeeder::ADMIN_DESGUACE));
        $this->assertSame(0, $viejo->tokens()->count());
        $this->assertTrue($nuevo->fresh()->hasRole(RoleSeeder::ADMIN_DESGUACE));

        // El antiguo administrador ya no entra en el inventario ni en la gestion de usuarios.
        $this->como($viejo);
        $this->getJson('/api/vehiculos')->assertStatus(403);
        $this->getJson('/api/usuarios')->assertStatus(403);
    }

    public function test_un_administrador_de_otra_sede_no_se_puede_reasignar_sin_sustituirlo(): void
    {
        $adminB = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE, $this->sedeB);
        $this->actuarComo(RoleSeeder::PLATAFORMA);

        $this->putJson("/api/desguaces/{$this->sedeA->id_desguace}/administrador", ['id_usuario' => $adminB->id])
            ->assertStatus(422);

        $this->assertSame($this->sedeB->id_desguace, $adminB->fresh()->id_desguace);
    }

    public function test_la_designacion_exige_una_sola_forma_de_datos(): void
    {
        $usuario = User::factory()->create();
        $this->actuarComo(RoleSeeder::PLATAFORMA);
        $ruta = "/api/desguaces/{$this->sedeA->id_desguace}/administrador";

        $this->putJson($ruta, [])->assertStatus(422);
        $this->putJson($ruta, ['id_usuario' => $usuario->id, 'email' => 'otro@test.test'])->assertStatus(422);
        $this->putJson($ruta, ['id_usuario' => 999999])->assertStatus(422);
    }

    public function test_la_plataforma_ve_todos_los_desguaces_pero_no_gestiona_equipos(): void
    {
        $this->actuarComo(RoleSeeder::PLATAFORMA);

        $this->getJson('/api/desguaces')->assertOk()->assertJsonCount(2, 'data');
        $this->postJson('/api/usuarios', [
            'name' => 'X', 'email' => 'x@test.test', 'password' => 'clave-segura-1', 'rol' => 'gerente',
        ])->assertStatus(403);
        $this->putJson('/api/usuarios/1/rol', ['rol' => 'gerente'])->assertStatus(403);
    }

    public function test_los_roles_locales_no_pueden_crear_desguaces_ni_designar_administradores(): void
    {
        foreach ([RoleSeeder::ADMIN_DESGUACE, RoleSeeder::GERENTE, RoleSeeder::OPERARIO] as $rol) {
            $this->actuarComo($rol, $this->sedeA);

            $this->postJson('/api/desguaces', $this->datosDesguace('Pirata'))->assertStatus(403);
            $this->putJson("/api/desguaces/{$this->sedeA->id_desguace}/administrador", ['id_usuario' => 1])
                ->assertStatus(403);
            $this->putJson('/api/usuarios/1/desguace', ['id_desguace' => $this->sedeB->id_desguace])
                ->assertStatus(403);
        }

        $this->assertSame(2, Desguace::withoutGlobalScopes()->count());
    }

    // ---------- Administrador de desguace ----------

    public function test_el_administrador_local_crea_su_equipo_y_el_servidor_asigna_la_sede(): void
    {
        $this->actuarComo(RoleSeeder::ADMIN_DESGUACE, $this->sedeA);

        $respuesta = $this->postJson('/api/usuarios', [
            'name' => 'Gema Gerente',
            'email' => 'gema@test.test',
            'password' => 'clave-segura-1',
            'rol' => RoleSeeder::GERENTE,
        ])->assertStatus(201);

        $creado = User::findOrFail($respuesta->json('data.id'));
        $this->assertSame($this->sedeA->id_desguace, $creado->id_desguace);
        $this->assertTrue($creado->hasRole(RoleSeeder::GERENTE));

        $this->postJson('/api/usuarios', [
            'name' => 'Oscar Operario',
            'email' => 'oscar@test.test',
            'password' => 'clave-segura-1',
            'rol' => RoleSeeder::OPERARIO,
        ])->assertStatus(201);

        $this->assertSame(2, User::whereIn('email', ['gema@test.test', 'oscar@test.test'])->where('id_desguace', $this->sedeA->id_desguace)->count());
    }

    public function test_el_administrador_local_no_puede_elegir_otra_sede_ni_roles_de_administracion(): void
    {
        $this->actuarComo(RoleSeeder::ADMIN_DESGUACE, $this->sedeA);
        $base = ['name' => 'Eva', 'email' => 'eva@test.test', 'password' => 'clave-segura-1'];

        $this->postJson('/api/usuarios', $base + ['rol' => 'gerente', 'id_desguace' => $this->sedeB->id_desguace])
            ->assertStatus(422);
        foreach ([RoleSeeder::ADMIN_DESGUACE, RoleSeeder::PLATAFORMA, RoleSeeder::ADMINISTRADOR] as $rol) {
            $this->postJson('/api/usuarios', $base + ['rol' => $rol])->assertStatus(422);
        }

        $this->assertSame(0, User::where('email', 'eva@test.test')->count());
    }

    public function test_el_administrador_local_edita_a_su_equipo_pero_no_cambia_su_sede(): void
    {
        $this->actuarComo(RoleSeeder::ADMIN_DESGUACE, $this->sedeA);
        $operario = $this->usuarioConRol(RoleSeeder::OPERARIO, $this->sedeA);

        $this->putJson("/api/usuarios/{$operario->id}", ['name' => 'Nuevo Nombre', 'rol' => 'gerente'])
            ->assertOk();
        $this->assertSame('Nuevo Nombre', $operario->fresh()->name);
        $this->assertTrue($operario->fresh()->hasRole(RoleSeeder::GERENTE));

        $this->putJson("/api/usuarios/{$operario->id}", ['id_desguace' => $this->sedeB->id_desguace])
            ->assertStatus(422);
        $this->assertSame($this->sedeA->id_desguace, $operario->fresh()->id_desguace);

        $this->deleteJson("/api/usuarios/{$operario->id}")->assertOk();
        $this->assertSoftDeleted('users', ['id' => $operario->id]);
    }

    public function test_el_administrador_local_no_toca_usuarios_de_otra_sede(): void
    {
        $this->actuarComo(RoleSeeder::ADMIN_DESGUACE, $this->sedeA);
        $ajeno = $this->usuarioConRol(RoleSeeder::OPERARIO, $this->sedeB);

        $this->getJson("/api/usuarios/{$ajeno->id}")->assertNotFound();
        $this->putJson("/api/usuarios/{$ajeno->id}", ['name' => 'Hackeado'])->assertNotFound();
        $this->putJson("/api/usuarios/{$ajeno->id}/rol", ['rol' => 'gerente'])->assertNotFound();
        $this->deleteJson("/api/usuarios/{$ajeno->id}/roles/operario")->assertNotFound();
        $this->deleteJson("/api/usuarios/{$ajeno->id}")->assertNotFound();

        $ajeno->refresh();
        $this->assertNotSame('Hackeado', $ajeno->name);
        $this->assertTrue($ajeno->hasRole(RoleSeeder::OPERARIO));
        $this->assertNull($ajeno->deleted_at);

        $ids = collect($this->getJson('/api/usuarios')->assertOk()->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($ajeno->id));
        $this->assertTrue($ids->contains($this->invA['autor']->id));
    }

    public function test_el_administrador_local_no_gestiona_cuentas_de_administracion(): void
    {
        $this->actuarComo(RoleSeeder::ADMIN_DESGUACE, $this->sedeA);
        $otroAdmin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE, $this->sedeA);

        $this->putJson("/api/usuarios/{$otroAdmin->id}", ['name' => 'X'])->assertStatus(403);
        $this->putJson("/api/usuarios/{$otroAdmin->id}/rol", ['rol' => 'operario'])->assertStatus(403);
        $this->deleteJson("/api/usuarios/{$otroAdmin->id}")->assertStatus(403);

        $this->assertTrue($otroAdmin->fresh()->hasRole(RoleSeeder::ADMIN_DESGUACE));
    }

    // ---------- Gerente y operario sobre el inventario de su sede ----------

    public function test_un_gerente_gestiona_el_inventario_de_su_sede(): void
    {
        $gerente = $this->actuarComo(RoleSeeder::GERENTE, $this->sedeA);

        $this->postJson('/api/vehiculos', $this->datosVehiculo($this->sedeA))->assertStatus(201);
        $this->putJson("/api/vehiculos/{$this->invA['vehiculo']->id_vehiculo}", $this->datosVehiculo($this->sedeA))
            ->assertOk();
        // El alta registra la Entrada inicial en un hueco de la sede; después el stock cambia con movimientos.
        $idPieza = $this->postJson('/api/piezas', [
            'nombre' => 'Faro', 'precio' => 30, 'estado' => 'Buen estado', 'stock_minimo' => 1,
            'id_tipo_pieza' => $this->invA['tipo']->id_pieza,
            'cantidad' => 3, 'id_etiqueta' => $this->invA['etiqueta']->id_etiqueta,
        ])->assertStatus(201)->assertJsonPath('data.stock', 3)->json('data.id_pieza');
        $this->postJson('/api/movimientos', [
            'id_pieza' => $idPieza, 'accion' => 'compra', 'cantidad' => 2,
            'destino' => $this->invA['etiqueta']->id_etiqueta,
        ])->assertStatus(201);
        $this->assertSame(5, Pieza::findOrFail($idPieza)->stock);
        $this->assertSame($gerente->id, Pieza::findOrFail($idPieza)->id_usuario);
        $this->postJson('/api/ubicaciones-vehiculo', [
            'id_vehiculo' => $this->invA['vehiculo']->id_vehiculo,
            'id_nivel' => $this->invA['nivel']->id_nivel,
            'fecha_inicio' => '2026-03-01',
        ])->assertStatus(201);
    }

    public function test_los_listados_solo_devuelven_registros_de_la_propia_sede(): void
    {
        $this->actuarComo(RoleSeeder::ADMIN_DESGUACE, $this->sedeA);
        $fichasB = $this->fichas($this->invB, $this->sedeB);
        $fichasA = $this->fichas($this->invA, $this->sedeA);

        foreach ($fichasA as $recurso => $idPropio) {
            $datos = $this->getJson("/api/{$recurso}")->assertOk()->json('data');
            $ids = collect($datos)->map(fn ($fila) => collect($fila)->first(fn ($v, $k) => str_starts_with($k, 'id_') || $k === 'categoria_id'))->all();

            $this->assertNotEmpty($datos, "Listado vacio en {$recurso}");
            $this->assertNotContains($fichasB[$recurso], $ids, "El listado de {$recurso} filtra datos de la otra sede");
        }

        $matriculas = collect($this->getJson('/api/vehiculos')->json('data'))->pluck('matricula');
        $this->assertTrue($matriculas->contains($this->invA['vehiculo']->matricula));
        $this->assertFalse($matriculas->contains($this->invB['vehiculo']->matricula));
    }

    public function test_la_ficha_de_otra_sede_por_url_directa_se_rechaza(): void
    {
        $this->actuarComo(RoleSeeder::ADMIN_DESGUACE, $this->sedeA);

        foreach ($this->fichas($this->invB, $this->sedeB) as $recurso => $id) {
            // Desguaces: sin permiso de edición (403). Movimientos: el historial no se edita ni se borra (405).
            $esperado = match ($recurso) {
                'desguaces' => 403,
                'movimientos' => 405,
                default => 404,
            };

            $this->getJson("/api/{$recurso}/{$id}")->assertNotFound();
            $this->putJson("/api/{$recurso}/{$id}", ['nombre' => 'x'])->assertStatus($esperado);
            $this->deleteJson("/api/{$recurso}/{$id}", ['motivo' => 'x'])->assertStatus($esperado);
        }

        // La propia sede si responde.
        foreach ($this->fichas($this->invA, $this->sedeA) as $recurso => $id) {
            $this->getJson("/api/{$recurso}/{$id}")->assertOk();
        }

        $this->assertNull($this->invB['vehiculo']->fresh()->deleted_at);
        $this->assertNull($this->invB['pieza']->fresh()->deleted_at);
    }

    public function test_un_gerente_y_un_operario_tampoco_ven_fichas_de_otra_sede(): void
    {
        foreach ([RoleSeeder::GERENTE, RoleSeeder::OPERARIO] as $rol) {
            $this->actuarComo($rol, $this->sedeA);

            $this->getJson("/api/vehiculos/{$this->invB['vehiculo']->id_vehiculo}")->assertNotFound();
            $this->getJson("/api/piezas/{$this->invB['pieza']->id_pieza}")->assertNotFound();
        }

        // El gerente consulta su sede; el de otra sede no existe para él.
        $this->actuarComo(RoleSeeder::GERENTE, $this->sedeA);
        $this->getJson("/api/desguaces/{$this->sedeB->id_desguace}")->assertNotFound();
        $this->getJson("/api/desguaces/{$this->sedeA->id_desguace}")->assertOk();

        // El operario no accede a módulos de gestión, ni siquiera de su propia sede.
        $this->actuarComo(RoleSeeder::OPERARIO, $this->sedeA);
        $this->getJson("/api/desguaces/{$this->sedeA->id_desguace}")->assertForbidden();
    }

    public function test_un_operario_consulta_su_sede_pero_no_puede_modificar_nada(): void
    {
        $this->actuarComo(RoleSeeder::OPERARIO, $this->sedeA);
        $vehiculo = $this->invA['vehiculo'];
        $pieza = $this->invA['pieza'];

        $this->getJson('/api/vehiculos')->assertOk();
        $this->getJson("/api/vehiculos/{$vehiculo->id_vehiculo}")->assertOk();
        $this->getJson('/api/piezas')->assertOk();
        $this->getJson("/api/piezas/{$pieza->id_pieza}")->assertOk();

        $this->postJson('/api/vehiculos', $this->datosVehiculo($this->sedeA))->assertStatus(403);
        $this->putJson("/api/vehiculos/{$vehiculo->id_vehiculo}", $this->datosVehiculo($this->sedeA))->assertStatus(403);
        $this->patchJson("/api/vehiculos/{$vehiculo->id_vehiculo}", ['marca' => 'X'])->assertStatus(403);
        $this->deleteJson("/api/vehiculos/{$vehiculo->id_vehiculo}")->assertStatus(403);
        $this->putJson("/api/piezas/{$pieza->id_pieza}", ['stock' => 500])->assertStatus(403);
        $this->deleteJson("/api/piezas/{$pieza->id_pieza}")->assertStatus(403);
        $this->postJson('/api/movimientos', [])->assertStatus(403);

        $this->assertNull($vehiculo->fresh()->deleted_at);
        $this->assertSame(5, $pieza->fresh()->stock);
    }

    // ---------- Formularios con identificadores de otra sede ----------

    public function test_un_formulario_con_un_identificador_de_otra_sede_se_rechaza(): void
    {
        $gerente = $this->actuarComo(RoleSeeder::GERENTE, $this->sedeA);
        $b = $this->invB;

        $casos = [
            'vehiculos' => [$this->datosVehiculo($this->sedeB), 'id_desguace'],
            'modulos' => [['id_desguace' => $this->sedeB->id_desguace, 'nombre_modulo' => 'M'], 'id_desguace'],
            'patios' => [['id_desguace' => $this->sedeB->id_desguace, 'codigo' => 'P', 'nombre' => 'Patio'], 'id_desguace'],
            'pasillos' => [['id_modulo' => $b['modulo']->id_modulo, 'numero_pasillo' => '1'], 'id_modulo'],
            'estanterias' => [['id_pasillo' => $b['pasillo']->id_pasillo, 'numero_estanteria' => '1'], 'id_pasillo'],
            'etiquetas' => [['id_estanteria' => $b['estanteria']->id_estanteria, 'numero_etiqueta' => '1'], 'id_estanteria'],
            'sectores' => [['id_patio' => $b['patio']->id_patio, 'codigo' => 'S', 'tipo_zona' => 'Z'], 'id_patio'],
            'filas' => [['id_sector' => $b['sector']->id_sector, 'numero_fila' => '1'], 'id_sector'],
            'posiciones' => [['id_fila' => $b['fila']->id_fila, 'numero_posicion' => '1', 'capacidad_maxima' => 2], 'id_fila'],
            'niveles' => [['id_posicion' => $b['posicion']->id_posicion, 'numero_nivel' => '1'], 'id_posicion'],
            'categorias' => [['nombre' => 'C', 'id_desguace' => $this->sedeA->id_desguace, 'id_modulo' => $b['modulo']->id_modulo], 'id_modulo'],
            'tipo-piezas' => [['nombre' => 'T', 'fabricante' => 'F', 'categoria' => 'C', 'id_categoria' => $b['categoria']->categoria_id], 'id_categoria'],
            'movimientos' => [['id_pieza' => $b['pieza']->id_pieza, 'accion' => 'venta', 'cantidad' => 1, 'origen' => 'pendiente'], 'id_pieza'],
            'piezas' => [[
                'nombre' => 'P', 'precio' => 1, 'estado' => 'Buen estado', 'stock_minimo' => 1,
                'id_tipo_pieza' => $b['tipo']->id_pieza, 'cantidad' => 1, 'id_etiqueta' => $this->invA['etiqueta']->id_etiqueta,
            ], 'id_tipo_pieza'],
        ];

        // Una pieza propia ubicada en un hueco de la otra sede, y un traslado a un hueco de la otra sede.
        $this->postJson('/api/piezas', [
            'nombre' => 'P', 'precio' => 1, 'estado' => 'Buen estado', 'stock_minimo' => 1,
            'id_tipo_pieza' => $this->invA['tipo']->id_pieza, 'cantidad' => 1, 'id_etiqueta' => $b['etiqueta']->id_etiqueta,
        ])->assertStatus(422)->assertJsonValidationErrors('id_etiqueta');
        $this->postJson('/api/movimientos', [
            'id_pieza' => $this->invA['pieza']->id_pieza, 'accion' => 'traslado', 'cantidad' => 1,
            'origen' => 'pendiente', 'destino' => $b['etiqueta']->id_etiqueta,
        ])->assertStatus(422)->assertJsonValidationErrors('destino');
        $this->assertSame(5, $this->invA['pieza']->fresh()->stock);

        foreach ($casos as $recurso => [$datos, $campo]) {
            $this->postJson("/api/{$recurso}", $datos)
                ->assertStatus(422)
                ->assertJsonValidationErrors($campo);
        }

        // Solo queda el vehiculo original de la sede B: ningun formulario creo registros ajenos.
        $this->assertSame(1, Vehiculo::withoutGlobalScopes()->where('id_desguace', $this->sedeB->id_desguace)->count());
    }

    public function test_los_formularios_rechazan_referencias_cruzadas_en_piezas_categorias_y_ubicaciones(): void
    {
        $this->actuarComo(RoleSeeder::GERENTE, $this->sedeA);
        $a = $this->invA;
        $b = $this->invB;

        // Pieza atribuida a un usuario de otra sede.
        $this->postJson('/api/piezas', [
            'nombre' => 'P', 'precio' => 1, 'estado' => 'Usada', 'stock' => 1, 'stock_minimo' => 1,
            'id_usuario' => $b['autor']->id, 'id_tipo_pieza' => $a['tipo']->id_pieza,
        ])->assertStatus(422)->assertJsonValidationErrors('id_usuario');

        // Categoria que referencia un vehiculo de otra sede.
        $this->postJson('/api/categorias', [
            'nombre' => 'C', 'id_desguace' => $this->sedeA->id_desguace,
            'id_modulo' => $a['modulo']->id_modulo, 'id_vehiculo' => $b['vehiculo']->id_vehiculo,
        ])->assertStatus(422)->assertJsonValidationErrors('id_vehiculo');

        // Ubicacion mezclando vehiculo de A con nivel de B, y al reves.
        $this->postJson('/api/ubicaciones-vehiculo', [
            'id_vehiculo' => $a['vehiculo']->id_vehiculo, 'id_nivel' => $b['nivel']->id_nivel, 'fecha_inicio' => '2026-03-01',
        ])->assertStatus(422)->assertJsonValidationErrors('id_nivel');
        $this->postJson('/api/ubicaciones-vehiculo', [
            'id_vehiculo' => $b['vehiculo']->id_vehiculo, 'id_nivel' => $a['nivel']->id_nivel, 'fecha_inicio' => '2026-03-01',
        ])->assertStatus(422)->assertJsonValidationErrors('id_vehiculo');

        $this->assertSame(2, UbicacionVehiculo::withoutGlobalScopes()->count());
    }

    public function test_una_edicion_no_puede_mover_un_registro_a_otra_sede(): void
    {
        $gerente = $this->actuarComo(RoleSeeder::GERENTE, $this->sedeA);
        $vehiculo = $this->invA['vehiculo'];
        $pieza = $this->invA['pieza'];

        $this->putJson("/api/vehiculos/{$vehiculo->id_vehiculo}", $this->datosVehiculo($this->sedeB))
            ->assertStatus(422);
        $this->putJson("/api/piezas/{$pieza->id_pieza}", [
            'nombre' => 'P', 'precio' => 1, 'estado' => 'Usada', 'stock' => 1, 'stock_minimo' => 1,
            'id_usuario' => $gerente->id, 'id_tipo_pieza' => $this->invB['tipo']->id_pieza,
        ])->assertStatus(422);

        $this->assertSame($this->sedeA->id_desguace, $vehiculo->fresh()->id_desguace);
        $this->assertSame($this->invA['tipo']->id_pieza, Pieza::findOrFail($pieza->id_pieza)->id_tipo_pieza);
    }

    // ---------- Cambio de sede ----------

    public function test_al_cambiar_la_sede_se_revoca_el_acceso_a_la_anterior(): void
    {
        $gerente = $this->usuarioConRol(RoleSeeder::GERENTE, $this->sedeA);
        $gerente->createToken('api-token');
        $idVehiculoA = $this->invA['vehiculo']->id_vehiculo;
        $idVehiculoB = $this->invB['vehiculo']->id_vehiculo;

        // Antes del cambio: ve la sede A y no la B.
        $this->como($gerente);
        $this->getJson("/api/vehiculos/{$idVehiculoA}")->assertOk();
        $this->getJson("/api/vehiculos/{$idVehiculoB}")->assertNotFound();

        // Procedimiento autorizado: la plataforma cambia la sede.
        $this->actuarComo(RoleSeeder::PLATAFORMA);
        $this->putJson("/api/usuarios/{$gerente->id}/desguace", ['id_desguace' => $this->sedeB->id_desguace])
            ->assertOk()
            ->assertJsonPath('data.id_desguace', $this->sedeB->id_desguace);

        $this->assertSame(0, $gerente->tokens()->count(), 'Los tokens anteriores deben revocarse');

        // Despues: pierde la A y gana la B.
        $this->como($gerente);
        $this->getJson("/api/vehiculos/{$idVehiculoA}")->assertNotFound();
        $this->getJson("/api/piezas/{$this->invA['pieza']->id_pieza}")->assertNotFound();
        $this->getJson("/api/vehiculos/{$idVehiculoB}")->assertOk();
        $this->putJson("/api/vehiculos/{$idVehiculoA}", $this->datosVehiculo($this->sedeA))->assertNotFound();
        $this->postJson('/api/vehiculos', $this->datosVehiculo($this->sedeA))->assertStatus(422);

        $ids = collect($this->getJson('/api/vehiculos')->json('data'))->pluck('id_vehiculo');
        $this->assertFalse($ids->contains($idVehiculoA));
        $this->assertTrue($ids->contains($idVehiculoB));
    }

    public function test_la_plataforma_no_puede_mover_administradores_ni_cuentas_de_plataforma_con_ese_procedimiento(): void
    {
        $admin = $this->usuarioConRol(RoleSeeder::ADMIN_DESGUACE, $this->sedeA);
        $otraPlataforma = $this->usuarioConRol(RoleSeeder::PLATAFORMA);
        $this->actuarComo(RoleSeeder::PLATAFORMA);

        $this->putJson("/api/usuarios/{$admin->id}/desguace", ['id_desguace' => $this->sedeB->id_desguace])
            ->assertStatus(422);
        $this->putJson("/api/usuarios/{$otraPlataforma->id}/desguace", ['id_desguace' => $this->sedeB->id_desguace])
            ->assertStatus(422);
        $this->putJson("/api/usuarios/{$admin->id}/desguace", ['id_desguace' => 999999])
            ->assertStatus(422);

        $this->assertSame($this->sedeA->id_desguace, $admin->fresh()->id_desguace);
        $this->assertNull($otraPlataforma->fresh()->id_desguace);
    }
}
