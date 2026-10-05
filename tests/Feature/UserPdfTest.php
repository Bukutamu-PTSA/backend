<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPdfTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role = 'admin'): User
    {
        return User::create([
            'username' => 'user_pdf_'.$role,
            'email' => 'user_pdf_'.$role.'@mail.com',
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
    }

    public function test_pdf_requires_authentication(): void
    {
        $this->get('/api/users/pdf')->assertUnauthorized();
    }

    public function test_non_admin_cannot_download_pdf(): void
    {
        $this->actingAs($this->createUser('pelapor'))
            ->get('/api/users/pdf')
            ->assertForbidden();
    }

    public function test_admin_can_download_pdf(): void
    {
        $admin = $this->createUser();

        $response = $this->actingAs($admin)->get('/api/users/pdf');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString(
            'filename="daftar-user-',
            $response->headers->get('Content-Disposition')
        );

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
