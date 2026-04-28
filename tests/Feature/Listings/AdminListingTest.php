<?php

namespace Tests\Feature\Listings;

use App\Application\Categories\Queries\ListCategories;
use App\Application\Secretariats\Queries\ListSecretariats;
use App\Models\Category;
use App\Models\Secretariat;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_listing_filters_and_paginates(): void
    {
        $secretariat = Secretariat::factory()->create();

        Category::factory()->count(11)->create([
            'secretariat_id' => $secretariat->id,
            'name' => 'Categoria comum',
        ]);

        Category::factory()->count(2)->create([
            'secretariat_id' => $secretariat->id,
            'name' => 'Categoria alvo',
        ]);

        $listing = app(ListCategories::class)->handle('alvo', 10);

        $this->assertSame(2, $listing->total());
        $this->assertCount(2, $listing->items());
    }

    public function test_secretariats_listing_filters_and_paginates(): void
    {
        Secretariat::factory()->count(14)->sequence(
            fn (Sequence $sequence) => ['name' => "Secretaria comum {$sequence->index}"],
        )->create();

        Secretariat::factory()->count(2)->sequence(
            fn (Sequence $sequence) => ['name' => "Secretaria foco {$sequence->index}"],
        )->create();

        $listing = app(ListSecretariats::class)->handle('foco', 10);

        $this->assertSame(2, $listing->total());
        $this->assertCount(2, $listing->items());
    }
}
