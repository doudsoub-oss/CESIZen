<?php

namespace Tests\Feature\Accessibility;

use Database\Seeders\CESIZenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccessibilityDeclarationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accessibility_declaration_page_is_publicly_accessible(): void
    {
        $this->get(route('accessibility'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('public/Accessibility'));
    }

    public function test_footer_navigation_links_to_the_accessibility_declaration(): void
    {
        $this->seed(CESIZenSeeder::class);

        $footer = $this->get(route('home'))->inertiaProps('navigation.footer');

        $this->assertTrue(
            collect($footer)->pluck('url')->contains('/accessibilite'),
            'Le pied de page doit contenir un lien vers la déclaration d\'accessibilité (/accessibilite).'
        );
    }
}
