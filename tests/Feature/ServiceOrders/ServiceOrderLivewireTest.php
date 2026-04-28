<?php

namespace Tests\Feature\ServiceOrders;

use App\Domain\ServiceOrders\ServiceOrderStatus;
use App\Livewire\Secretariat\ServiceOrderManager;
use App\Models\Category;
use App\Models\Secretariat;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceOrderLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_service_order_manager(): void
    {
        $secretariat = Secretariat::factory()->create();
        $user = User::factory()->create(['secretariat_id' => $secretariat->id]);

        $this->actingAs($user)
            ->get(route('secretariats.ods', $secretariat))
            ->assertOk()
            ->assertSee($secretariat->name);
    }

    public function test_can_create_ods_via_livewire(): void
    {
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);
        $user = User::factory()->create(['secretariat_id' => $secretariat->id]);

        Livewire::actingAs($user)
            ->test(ServiceOrderManager::class, ['secretariat' => $secretariat])
            ->set('form.title', 'Nova ODS Teste')
            ->set('form.location', 'Rua de Teste')
            ->set('form.categoryId', $category->id)
            ->set('form.isUrgent', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('ods-saved');

        $this->assertDatabaseHas('service_orders', [
            'title' => 'Nova ODS Teste',
            'location' => 'Rua de Teste',
            'category_id' => $category->id,
            'is_urgent' => true,
            'secretariat_id' => $secretariat->id,
        ]);
    }

    public function test_can_edit_ods_via_livewire(): void
    {
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);
        $user = User::factory()->create(['secretariat_id' => $secretariat->id]);
        $ods = ServiceOrder::factory()->create([
            'secretariat_id' => $secretariat->id,
            'category_id' => $category->id,
            'title' => 'Titulo Antigo',
        ]);

        Livewire::actingAs($user)
            ->test(ServiceOrderManager::class, ['secretariat' => $secretariat])
            ->call('edit', $ods->id)
            ->assertSet('form.title', 'Titulo Antigo')
            ->set('form.title', 'Titulo Atualizado')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('ods-saved');

        $this->assertDatabaseHas('service_orders', [
            'id' => $ods->id,
            'title' => 'Titulo Atualizado',
        ]);
    }

    public function test_can_update_status_via_livewire(): void
    {
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);
        $user = User::factory()->create(['secretariat_id' => $secretariat->id]);
        $ods = ServiceOrder::factory()->create([
            'secretariat_id' => $secretariat->id,
            'category_id' => $category->id,
            'status' => ServiceOrderStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(ServiceOrderManager::class, ['secretariat' => $secretariat])
            ->call('updateStatus', $ods->id, ServiceOrderStatus::InProgress->value)
            ->assertHasNoErrors()
            ->assertDispatched('ods-status-updated');

        $this->assertDatabaseHas('service_orders', [
            'id' => $ods->id,
            'status' => ServiceOrderStatus::InProgress->value,
        ]);
    }

    public function test_can_filter_ods_by_search(): void
    {
        $secretariat = Secretariat::factory()->create();
        $user = User::factory()->create(['secretariat_id' => $secretariat->id]);

        ServiceOrder::factory()->forSecretariat($secretariat)->create([
            'title' => 'Reparo Luz',
            'status' => ServiceOrderStatus::Pending,
        ]);
        ServiceOrder::factory()->forSecretariat($secretariat)->create([
            'title' => 'Poda Arvore',
            'status' => ServiceOrderStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(ServiceOrderManager::class, ['secretariat' => $secretariat])
            ->set('search', 'Luz')
            ->assertSee('Reparo Luz')
            ->assertDontSee('Poda Arvore');
    }

    public function test_can_manage_checklist_items_via_livewire(): void
    {
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);
        $user = User::factory()->create(['secretariat_id' => $secretariat->id]);
        $ods = ServiceOrder::factory()->create([
            'secretariat_id' => $secretariat->id,
            'category_id' => $category->id,
        ]);

        Livewire::actingAs($user)
            ->test(ServiceOrderManager::class, ['secretariat' => $secretariat])
            ->call('edit', $ods->id)
            ->set('form.newChecklistItem', 'Etapa 1')
            ->call('addChecklistItem')
            ->assertCount('form.checklistItems', 1)
            ->assertSet('form.checklistItems.0.label', 'Etapa 1');

        $this->assertDatabaseHas('ods_checklists', [
            'service_order_id' => $ods->id,
            'label' => 'Etapa 1',
        ]);

        Livewire::actingAs($user)
            ->test(ServiceOrderManager::class, ['secretariat' => $secretariat])
            ->call('edit', $ods->id)
            ->call('toggleChecklistItem', 0)
            ->assertSet('form.checklistItems.0.is_completed', true);

        $this->assertDatabaseHas('ods_checklists', [
            'service_order_id' => $ods->id,
            'label' => 'Etapa 1',
            'is_completed' => true,
        ]);
    }
}
