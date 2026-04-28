<?php

namespace Tests\Feature\ServiceOrders;

use App\Application\ServiceOrders\ChangeServiceOrderStatus;
use App\Application\ServiceOrders\CreateServiceOrder;
use App\Application\ServiceOrders\Data\CreateServiceOrderData;
use App\Application\ServiceOrders\Data\UpdateServiceOrderData;
use App\Application\ServiceOrders\DeleteServiceOrder;
use App\Application\ServiceOrders\GetServiceOrder;
use App\Application\ServiceOrders\UpdateServiceOrder;
use App\Domain\ServiceOrders\Exceptions\InvalidServiceOrderCategory;
use App\Domain\ServiceOrders\Exceptions\ServiceOrderNotFound;
use App\Domain\ServiceOrders\ServiceOrderStatus;
use App\Models\Category;
use App\Models\Secretariat;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_order_code_is_generated_from_persisted_id(): void
    {
        $user = User::factory()->create();
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);
        $data = CreateServiceOrderData::fromArray([
            'title' => 'ODS 1',
            'location' => 'Rua A',
            'category_id' => $category->id,
            'due_date' => null,
            'is_urgent' => false,
            'observation' => null,
        ]);

        $this->actingAs($user);

        $first = app(CreateServiceOrder::class)->handle($secretariat->id, $user->id, $data);
        $second = app(CreateServiceOrder::class)->handle($secretariat->id, $user->id, CreateServiceOrderData::fromArray([
            'title' => 'ODS 2',
            'location' => 'Rua B',
            'category_id' => $category->id,
            'due_date' => null,
            'is_urgent' => true,
            'observation' => null,
        ]));

        $this->assertSame(ServiceOrderStatus::Pending, $first->status);
        $this->assertMatchesRegularExpression('/^ODS-\d{6}$/', $first->code);
        $this->assertSame(ServiceOrder::codeFromId($first->id), $first->code);
        $this->assertSame(ServiceOrder::codeFromId($second->id), $second->code);
        $this->assertNotSame($first->code, $second->code);
    }

    public function test_service_order_can_be_created_with_checklist_items(): void
    {
        $user = User::factory()->create();
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);

        $this->actingAs($user);

        $serviceOrder = app(CreateServiceOrder::class)->handle(
            $secretariat->id,
            $user->id,
            CreateServiceOrderData::fromArray([
                'title' => 'ODS com checklist',
                'location' => 'Rua A',
                'category_id' => $category->id,
                'due_date' => null,
                'is_urgent' => false,
                'observation' => null,
                'checklist_items' => [
                    ['label' => 'Visitar local', 'is_completed' => false],
                    ['label' => 'Executar servico', 'is_completed' => true],
                ],
            ]),
        );

        $this->assertCount(2, $serviceOrder->checklistItems);
        $this->assertDatabaseHas('ods_checklists', [
            'service_order_id' => $serviceOrder->id,
            'label' => 'Visitar local',
            'is_completed' => false,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('ods_checklists', [
            'service_order_id' => $serviceOrder->id,
            'label' => 'Executar servico',
            'is_completed' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_update_service_order_preserves_existing_status(): void
    {
        $user = User::factory()->create();
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);
        $serviceOrder = ServiceOrder::factory()
            ->forSecretariat($secretariat)
            ->create([
                'category_id' => $category->id,
                'status' => ServiceOrderStatus::InProgress,
            ]);

        $this->actingAs($user);

        $updated = app(UpdateServiceOrder::class)->handle(
            $secretariat->id,
            $user->id,
            $serviceOrder->id,
            UpdateServiceOrderData::fromArray([
                'title' => 'Titulo atualizado',
                'location' => 'Rua Atualizada',
                'category_id' => $category->id,
                'due_date' => '2026-05-01',
                'is_urgent' => true,
                'observation' => 'Obs atualizada',
            ]),
        );

        $this->assertSame(ServiceOrderStatus::InProgress, $updated->status);
        $this->assertDatabaseHas('service_orders', [
            'id' => $serviceOrder->id,
            'status' => ServiceOrderStatus::InProgress->value,
        ]);
    }

    public function test_service_order_checklist_items_can_be_updated(): void
    {
        $user = User::factory()->create();
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);
        $serviceOrder = ServiceOrder::factory()
            ->forSecretariat($secretariat)
            ->create([
                'category_id' => $category->id,
            ]);

        $serviceOrder->checklistItems()->createMany([
            ['label' => 'Item antigo 1', 'is_completed' => false, 'sort_order' => 0],
            ['label' => 'Item antigo 2', 'is_completed' => false, 'sort_order' => 1],
        ]);

        $this->actingAs($user);

        $updated = app(UpdateServiceOrder::class)->handle(
            $secretariat->id,
            $user->id,
            $serviceOrder->id,
            UpdateServiceOrderData::fromArray([
                'title' => 'ODS atualizada',
                'location' => 'Rua Atualizada',
                'category_id' => $category->id,
                'due_date' => null,
                'is_urgent' => false,
                'observation' => null,
                'checklist_items' => [
                    ['label' => 'Novo item 1', 'is_completed' => true],
                    ['label' => 'Novo item 2', 'is_completed' => false],
                ],
            ]),
        );

        $this->assertCount(2, $updated->checklistItems);
        $this->assertDatabaseMissing('ods_checklists', [
            'service_order_id' => $serviceOrder->id,
            'label' => 'Item antigo 1',
        ]);
        $this->assertDatabaseHas('ods_checklists', [
            'service_order_id' => $serviceOrder->id,
            'label' => 'Novo item 1',
            'is_completed' => true,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('ods_checklists', [
            'service_order_id' => $serviceOrder->id,
            'label' => 'Novo item 2',
            'is_completed' => false,
            'sort_order' => 1,
        ]);
    }

    public function test_empty_checklist_labels_are_ignored(): void
    {
        $user = User::factory()->create();
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);

        $this->actingAs($user);

        $serviceOrder = app(CreateServiceOrder::class)->handle(
            $secretariat->id,
            $user->id,
            CreateServiceOrderData::fromArray([
                'title' => 'ODS com labels vazios',
                'location' => 'Rua A',
                'category_id' => $category->id,
                'due_date' => null,
                'is_urgent' => false,
                'observation' => null,
                'checklist_items' => [
                    ['label' => '  ', 'is_completed' => false],
                    ['label' => '', 'is_completed' => true],
                    ['label' => 'Item Valido', 'is_completed' => false],
                ],
            ]),
        );

        $this->assertCount(1, $serviceOrder->checklistItems);
        $this->assertSame('Item Valido', $serviceOrder->checklistItems[0]->label);
    }

    public function test_get_service_order_returns_scoped_record_with_checklist_items(): void
    {
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);
        $serviceOrder = ServiceOrder::factory()->create([
            'secretariat_id' => $secretariat->id,
            'category_id' => $category->id,
        ]);

        $serviceOrder->checklistItems()->createMany([
            ['label' => 'Item 1', 'is_completed' => false, 'sort_order' => 0],
            ['label' => 'Item 2', 'is_completed' => true, 'sort_order' => 1],
        ]);

        $loaded = app(GetServiceOrder::class)->handle($secretariat->id, $serviceOrder->id);

        $this->assertTrue($loaded->is($serviceOrder));
        $this->assertCount(2, $loaded->checklistItems);
        $this->assertSame('Item 1', $loaded->checklistItems[0]->label);
    }

    public function test_get_service_order_rejects_record_from_other_secretariat(): void
    {
        $secretariat = Secretariat::factory()->create();
        $otherSecretariat = Secretariat::factory()->create();
        $serviceOrder = ServiceOrder::factory()->forSecretariat($otherSecretariat)->create();

        $this->expectException(ServiceOrderNotFound::class);

        app(GetServiceOrder::class)->handle($secretariat->id, $serviceOrder->id);
    }

    public function test_create_service_order_use_case_rejects_category_from_other_secretariat(): void
    {
        $user = User::factory()->create();
        $secretariat = Secretariat::factory()->create();
        $otherSecretariat = Secretariat::factory()->create();
        $foreignCategory = Category::factory()->create(['secretariat_id' => $otherSecretariat->id]);

        $this->actingAs($user);

        $this->expectException(InvalidServiceOrderCategory::class);

        app(CreateServiceOrder::class)->handle(
            $secretariat->id,
            $user->id,
            CreateServiceOrderData::fromArray([
                'title' => 'ODS invalida',
                'location' => 'Rua X',
                'category_id' => $foreignCategory->id,
                'due_date' => null,
                'is_urgent' => false,
                'observation' => null,
            ]),
        );
    }

    public function test_delete_service_order_soft_deletes_scoped_record(): void
    {
        $secretariat = Secretariat::factory()->create();
        $serviceOrder = ServiceOrder::factory()->forSecretariat($secretariat)->create();

        app(DeleteServiceOrder::class)->handle($secretariat->id, $serviceOrder->id);

        $this->assertSoftDeleted('service_orders', [
            'id' => $serviceOrder->id,
            'secretariat_id' => $secretariat->id,
        ]);
    }

    public function test_delete_service_order_rejects_record_from_other_secretariat(): void
    {
        $secretariat = Secretariat::factory()->create();
        $otherSecretariat = Secretariat::factory()->create();
        $serviceOrder = ServiceOrder::factory()->forSecretariat($otherSecretariat)->create();

        $this->expectException(ServiceOrderNotFound::class);

        app(DeleteServiceOrder::class)->handle($secretariat->id, $serviceOrder->id);
    }

    public function test_service_order_data_can_be_built_from_service_order_for_form_usage(): void
    {
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);
        $serviceOrder = ServiceOrder::factory()->create([
            'secretariat_id' => $secretariat->id,
            'category_id' => $category->id,
            'title' => '  ODS teste  ',
            'location' => ' Rua A ',
            'observation' => ' Observacao ',
            'due_date' => '2026-05-10',
            'is_urgent' => true,
            'status' => ServiceOrderStatus::Pending,
        ]);

        $serviceOrder->checklistItems()->createMany([
            ['label' => 'Item 1', 'is_completed' => false],
            ['label' => 'Item 2', 'is_completed' => true],
        ]);

        $data = UpdateServiceOrderData::fromServiceOrder($serviceOrder->fresh(['checklistItems', 'histories']));

        $this->assertSame([
            'title' => '  ODS teste  ',
            'location' => 'Rua A',
            'categoryId' => $category->id,
            'dueDate' => '2026-05-10',
            'isUrgent' => true,
            'observation' => 'Observacao',
            'status' => 'pending',
            'checklistItems' => [
                ['label' => 'Item 1', 'is_completed' => false],
                ['label' => 'Item 2', 'is_completed' => true],
            ],
            'historyItems' => [],
        ], $data->toFormState());
    }

    public function test_service_order_status_transitions_are_flexible(): void
    {
        $serviceOrder = ServiceOrder::factory()->create([
            'status' => ServiceOrderStatus::Pending,
        ]);

        $serviceOrder->changeStatus(ServiceOrderStatus::InProgress);
        $serviceOrder->refresh();
        $this->assertSame(ServiceOrderStatus::InProgress, $serviceOrder->status);

        $serviceOrder->changeStatus(ServiceOrderStatus::Pending);
        $serviceOrder->refresh();
        $this->assertSame(ServiceOrderStatus::Pending, $serviceOrder->status);

        $serviceOrder->changeStatus(ServiceOrderStatus::Completed);
        $serviceOrder->refresh();
        $this->assertSame(ServiceOrderStatus::Completed, $serviceOrder->status);

        // Now allowed to move back from Completed
        $serviceOrder->changeStatus(ServiceOrderStatus::InProgress);
        $serviceOrder->refresh();
        $this->assertSame(ServiceOrderStatus::InProgress, $serviceOrder->status);

        $serviceOrder->changeStatus(ServiceOrderStatus::Pending);
        $serviceOrder->refresh();
        $this->assertSame(ServiceOrderStatus::Pending, $serviceOrder->status);
    }

    public function test_change_service_order_status_use_case_updates_scoped_record(): void
    {
        $user = User::factory()->create();
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);
        $serviceOrder = ServiceOrder::factory()->create([
            'secretariat_id' => $secretariat->id,
            'category_id' => $category->id,
            'status' => ServiceOrderStatus::Pending,
        ]);

        $this->actingAs($user);

        $updated = app(ChangeServiceOrderStatus::class)->handle(
            $secretariat->id,
            $user->id,
            $serviceOrder->id,
            ServiceOrderStatus::InProgress,
        );

        $this->assertSame(ServiceOrderStatus::InProgress, $updated->status);
        $this->assertDatabaseHas('service_orders', [
            'id' => $serviceOrder->id,
            'status' => ServiceOrderStatus::InProgress->value,
        ]);

        $this->assertDatabaseHas('ods_histories', [
            'service_order_id' => $serviceOrder->id,
            'user_id' => $user->id,
        ]);

        $history = $updated->histories()->first();
        $this->assertSame('pending', $history->metadata['status']['from']);
        $this->assertSame('in_progress', $history->metadata['status']['to']);
    }

    public function test_service_order_code_is_unique_even_with_soft_deletes(): void
    {
        $user = User::factory()->create();
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);

        $this->actingAs($user);

        $ods1 = app(CreateServiceOrder::class)->handle($secretariat->id, $user->id, CreateServiceOrderData::fromArray([
            'title' => 'ODS 1',
            'location' => 'Rua A',
            'category_id' => $category->id,
            'due_date' => null,
            'is_urgent' => false,
            'observation' => null,
        ]));

        $code1 = $ods1->code;

        // Soft delete ods1
        $ods1->delete();

        // Create ods2 - should have a different code because it will have a different ID
        $ods2 = app(CreateServiceOrder::class)->handle($secretariat->id, $user->id, CreateServiceOrderData::fromArray([
            'title' => 'ODS 2',
            'location' => 'Rua B',
            'category_id' => $category->id,
            'due_date' => null,
            'is_urgent' => false,
            'observation' => null,
        ]));

        $this->assertNotSame($code1, $ods2->code);
        $this->assertDatabaseHas('service_orders', ['code' => $code1]);
        $this->assertDatabaseHas('service_orders', ['code' => $ods2->code]);
    }
}
