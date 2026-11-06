<?php

namespace Tests\Feature;

use App\Models\Categoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaApiTest extends TestCase
{
    // Recrea las tablas en la base de tests antes de cada prueba.
    use RefreshDatabase;

    private array $datosValidos = [
        'nombre' => 'Motor',
    ];

    public function test_index_devuelve_las_categorias(): void
    {
        Categoria::factory()->count(2)->create();

        $this->getJson('/api/categorias')
            ->assertOk()
            ->assertJsonPath('success', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_show_devuelve_una_categoria(): void
    {
        $categoria = Categoria::factory()->create();

        $this->getJson('/api/categorias/'.$categoria->id)
            ->assertOk()
            ->assertJsonPath('data.nombre', $categoria->nombre);
    }

    public function test_store_crea_una_categoria(): void
    {
        $this->postJson('/api/categorias', $this->datosValidos)
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Motor');

        $this->assertDatabaseHas('categorias', ['nombre' => 'Motor']);
    }

    public function test_store_valida_los_campos_obligatorios(): void
    {
        $this->postJson('/api/categorias', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    public function test_update_modifica_una_categoria(): void
    {
        $categoria = Categoria::factory()->create();

        $this->putJson('/api/categorias/'.$categoria->id, $this->datosValidos)
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Motor');
    }

    public function test_patch_modifica_solo_los_campos_enviados(): void
    {
        $categoria = Categoria::factory()->create();

        $this->patchJson('/api/categorias/'.$categoria->id, ['nombre' => 'Carroceria'])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Carroceria');
    }

    public function test_destroy_elimina_una_categoria(): void
    {
        $categoria = Categoria::factory()->create();

        $this->deleteJson('/api/categorias/'.$categoria->id)
            ->assertOk()
            ->assertJsonPath('success', 1);

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_show_devuelve_404_si_no_existe(): void
    {
        $this->getJson('/api/categorias/999999')
            ->assertNotFound();
    }
}
