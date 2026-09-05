<?php

namespace Tests\Feature\Listings;

use App\Application\Categories\Queries\ListCategories;
use App\Application\Teams\Queries\ListTeams;
use App\Models\Category;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_listing_filters_and_paginates(): void
    {
        $team = Team::factory()->create();

        Category::factory()->count(11)->create([
            'team_id' => $team->id,
            'name' => 'Categoria comum',
        ]);

        Category::factory()->count(2)->create([
            'team_id' => $team->id,
            'name' => 'Categoria alvo',
        ]);

        $listing = app(ListCategories::class)->handle('alvo', 10);

        $this->assertSame(2, $listing->total());
        $this->assertCount(2, $listing->items());
    }

    public function test_teams_listing_filters_and_paginates(): void
    {
        Team::factory()->count(14)->sequence(
            fn (Sequence $sequence) => ['name' => "Equipe comum {$sequence->index}"],
        )->create();

        Team::factory()->count(2)->sequence(
            fn (Sequence $sequence) => ['name' => "Equipe foco {$sequence->index}"],
        )->create();

        $listing = app(ListTeams::class)->handle('foco', 10);

        $this->assertSame(2, $listing->total());
        $this->assertCount(2, $listing->items());
    }
}
