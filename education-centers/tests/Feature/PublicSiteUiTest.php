<?php

namespace Tests\Feature;

use App\Models\Center;
use App\Models\Formation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_new_landing(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        // The hero heading highlights the word «ici.» with nested markup,
        // so the two visible parts are asserted separately.
        $response->assertSee('Votre avenir commence');
        $response->assertSee('ici.');
        $response->assertSee('Découvrir nos formations');
        $response->assertSee('S’inscrire');
    }

    public function test_public_centers_and_formations_pages_render(): void
    {
        $center = Center::factory()->create([
            'name' => 'Centre Berlin',
            'code' => 'BERLIN',
            'city' => 'Berlin',
            'status' => 'active',
        ]);

        Formation::create([
            'center_id' => $center->id,
            'name' => 'Deutsch A1 Berlin',
            'code' => 'DE-A1',
            'duration_hours' => 120,
            'duration_weeks' => 12,
            'price' => 990,
            'currency' => 'EUR',
            'status' => 'active',
        ]);

        $this->get(route('public.centers'))
            ->assertOk()
            ->assertSee('Centre Berlin');

        $this->get(route('public.formations'))
            ->assertOk()
            ->assertSee('Deutsch A1 Berlin');

        $this->get(route('public.contact'))
            ->assertOk()
            ->assertSee('Contact');

        $this->get(route('public.apply'))
            ->assertOk()
            ->assertSee('Candidature');
    }
}
