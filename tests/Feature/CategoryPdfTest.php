<?php

namespace Tests\Feature;

use App\Models\ComplaintCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_requires_authentication(): void
    {
        $this->get('/api/v1/categories/pdf')->assertUnauthorized();
    }

    public function test_pdf_lists_categories(): void
    {
        $user = User::create([
            'username' => 'category_pdf_user',
            'email' => 'category_pdf@mail.com',
            'password' => bcrypt('password'),
            'role' => 'pelapor',
        ]);

        ComplaintCategory::create([
            'category_code' => 'K3',
            'category_name' => 'Keselamatan Kerja',
        ]);

        $response = $this->actingAs($user)->get('/api/v1/categories/pdf');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString(
            'filename="daftar-kategori-',
            $response->headers->get('Content-Disposition')
        );

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
