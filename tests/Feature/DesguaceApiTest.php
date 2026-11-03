<?php

namespace Tests\Feature;

use App\Models\Desguace;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DesguaceApiTest extends TestCase
{
    // Revierte los cambios de cada prueba sin recrear tablas ni borrar datos.
    use DatabaseTransactions;

    private array $datosValidos = [
        'nombre' => 'Desguace Prueba',
        'direccion' => 'Calle Falsa 123',
        'numero_telefono' => '600111222',
        'ubicacion' => 'Sevilla'
    ];

    public function test_index_devuelve_los_desguaces(): void
    {
        Desguace::factory()->count(2)->create();

        $this->getJson('/api/desguaces')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_show_devuelve_un_desguace(): void
    {
        $desguace = Desguace::factory()->create();

        $this->getJson('/api/desguaces/' . $desguace->id)
            ->assertOk()
            ->assertJsonPath('data.nombre', $desguace->nombre);
    }

    public function test_store_crea_un_desguace(): void
    {
        $this->postJson('/api/desguaces', $this->datosValidos)
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Desguace Prueba');

        $this->assertDatabaseHas('desguaces', ['nombre' => 'Desguace Prueba']);
    }

    public function test_store_valida_los_campos_obligatorios(): void
    {
        $this->postJson('/api/desguaces', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre', 'direccion', 'numero_telefono', 'ubicacion']);
    }

    public function test_update_modifica_un_desguace(): void
    {
        $desguace = Desguace::factory()->create();

        $this->putJson('/api/desguaces/' . $desguace->id, $this->datosValidos)
            ->assertOk()
            ->assertJsonPath('data.ubicacion', 'Sevilla');
    }

    public function test_patch_modifica_solo_los_campos_enviados(): void
    {
        $desguace = Desguace::factory()->create();

        $this->patchJson('/api/desguaces/' . $desguace->id, ['ubicacion' => 'Madrid'])
            ->assertOk()
            ->assertJsonPath('data.ubicacion', 'Madrid');
    }

    public function test_destroy_elimina_un_desguace(): void
    {
        $desguace = Desguace::factory()->create();

        $this->deleteJson('/api/desguaces/' . $desguace->id)
            ->assertOk()
            ->assertJsonPath('success', 1);

        $this->assertDatabaseMissing('desguaces', ['id' => $desguace->id]);
    }

    public function test_show_devuelve_404_si_no_existe(): void
    {
        $this->getJson('/api/desguaces/999999')
            ->assertNotFound();
    }
}
