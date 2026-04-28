<?php

namespace Tests\Feature\Listings;

use App\Application\ServiceOrders\Queries\ListServiceOrders;
use App\Domain\ServiceOrders\ServiceOrderStatus;
use App\Livewire\Secretariat\ServiceOrderManager;
use App\Models\Category;
use App\Models\Secretariat;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceOrderListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_order_listing_is_paginated_and_filtered_by_search(): void
    {
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);

        ServiceOrder::factory()->forSecretariat($secretariat)->count(12)->create([
            'category_id' => $category->id,
            'title' => 'Servico comum',
            'status' => ServiceOrderStatus::Pending,
        ]);

        ServiceOrder::factory()->forSecretariat($secretariat)->count(3)->create([
            'category_id' => $category->id,
            'title' => 'Reparo urgente',
            'is_urgent' => true,
            'status' => ServiceOrderStatus::Pending,
        ]);

        $listing = app(ListServiceOrders::class)->handle($secretariat->id, 'reparo', [], 5);

        $this->assertSame(3, $listing->summary['total']);
        $this->assertSame(3, $listing->summary['urgent']);
        $this->assertSame(3, $listing->serviceOrders->total());
        $this->assertCount(3, $listing->serviceOrders->items());
    }

    public function test_service_order_listing_summary_counts_completed_records(): void
    {
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);

        ServiceOrder::factory()->forSecretariat($secretariat)->count(2)->create([
            'category_id' => $category->id,
            'status' => ServiceOrderStatus::Completed,
        ]);

        ServiceOrder::factory()->forSecretariat($secretariat)->count(4)->create([
            'category_id' => $category->id,
            'status' => ServiceOrderStatus::Pending,
        ]);

        $listing = app(ListServiceOrders::class)->handle($secretariat->id, '', [], 3);

        $this->assertSame(4, $listing->summary['total']);
        $this->assertSame(2, $listing->summary['completed']);
        $this->assertCount(3, $listing->serviceOrders->items());
        $this->assertSame(2, $listing->serviceOrders->lastPage());
    }

    public function test_service_order_listing_respects_secretariat_scope(): void
    {
        $secretariat = Secretariat::factory()->create();
        $otherSecretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);
        $otherCategory = Category::factory()->create(['secretariat_id' => $otherSecretariat->id]);

        ServiceOrder::factory()->forSecretariat($secretariat)->count(2)->create([
            'category_id' => $category->id,
            'title' => 'ODS da secretaria correta',
            'status' => ServiceOrderStatus::Pending,
            'is_urgent' => false,
        ]);

        ServiceOrder::factory()->forSecretariat($otherSecretariat)->count(4)->create([
            'category_id' => $otherCategory->id,
            'title' => 'ODS de outra secretaria',
            'is_urgent' => true,
            'status' => ServiceOrderStatus::Completed,
        ]);

        $listing = app(ListServiceOrders::class)->handle($secretariat->id, '', [], 10);

        $this->assertSame(2, $listing->summary['total']);
        $this->assertSame(0, $listing->summary['urgent']);
        $this->assertSame(0, $listing->summary['completed']);
        $this->assertSame(2, $listing->serviceOrders->total());
    }

    public function test_service_order_listing_can_filter_by_category_status_and_urgency(): void
    {
        $secretariat = Secretariat::factory()->create();
        $lighting = Category::factory()->create([
            'secretariat_id' => $secretariat->id,
            'name' => 'Iluminacao',
        ]);
        $cleaning = Category::factory()->create([
            'secretariat_id' => $secretariat->id,
            'name' => 'Limpeza',
        ]);

        ServiceOrder::factory()->forSecretariat($secretariat)->create([
            'category_id' => $lighting->id,
            'title' => 'Troca de lampada',
            'status' => ServiceOrderStatus::Pending,
            'is_urgent' => true,
        ]);

        ServiceOrder::factory()->forSecretariat($secretariat)->create([
            'category_id' => $lighting->id,
            'title' => 'Poste concluido',
            'status' => ServiceOrderStatus::Completed,
            'is_urgent' => true,
        ]);

        ServiceOrder::factory()->forSecretariat($secretariat)->create([
            'category_id' => $cleaning->id,
            'title' => 'Varricao comum',
            'status' => ServiceOrderStatus::Completed,
            'is_urgent' => false,
        ]);

        $listing = app(ListServiceOrders::class)->handle($secretariat->id, '', [
            'category_id' => $lighting->id,
            'status' => ServiceOrderStatus::Completed->value,
            'urgent' => true,
        ], 10);

        $this->assertSame(1, $listing->summary['total']);
        $this->assertSame(1, $listing->summary['urgent']);
        $this->assertSame(1, $listing->summary['completed']);
        $this->assertSame('Poste concluido', $listing->serviceOrders->items()[0]->title);
    }

    public function test_service_order_top_summary_cards_work_as_quick_filters(): void
    {
        $secretariat = Secretariat::factory()->create();
        $category = Category::factory()->create(['secretariat_id' => $secretariat->id]);
        $user = User::factory()->create(['secretariat_id' => $secretariat->id]);

        ServiceOrder::factory()->forSecretariat($secretariat)->create([
            'category_id' => $category->id,
            'title' => 'Ordem urgente',
            'status' => ServiceOrderStatus::Pending,
            'is_urgent' => true,
        ]);

        ServiceOrder::factory()->forSecretariat($secretariat)->create([
            'category_id' => $category->id,
            'title' => 'Ordem concluida',
            'status' => ServiceOrderStatus::Completed,
            'is_urgent' => false,
        ]);

        Livewire::actingAs($user)
            ->test(ServiceOrderManager::class, ['secretariat' => $secretariat])
            ->call('applyQuickFilter', 'urgent')
            ->assertSet('quickFilter', 'urgent')
            ->assertSee('Ordem urgente')
            ->assertDontSee('Ordem concluida')
            ->call('applyQuickFilter', 'completed')
            ->assertSet('quickFilter', 'completed')
            ->assertSee('Ordem concluida')
            ->assertDontSee('Ordem urgente')
            ->call('applyQuickFilter', 'total')
            ->assertSet('quickFilter', '')
            ->assertSee('Ordem urgente')
            ->assertDontSee('Ordem concluida');
    }
}
