<?php

namespace Tests\Feature;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_faqs(): void
    {
        Faq::create(['question' => 'Apa itu PTSA?', 'answer' => 'Program Tahunan Serikat Pekerja.']);
        Faq::create(['question' => 'Bagaimana cara melapor?', 'answer' => 'Isi formulir pengaduan.']);

        $this->getJson('/api/faqs')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.question', 'Apa itu PTSA?');
    }

    public function test_store_creates_faq(): void
    {
        $response = $this->postJson('/api/faqs', [
            'question' => 'Apa itu PTSA?',
            'answer' => 'Program Tahunan Serikat Pekerja.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.question', 'Apa itu PTSA?')
            ->assertJsonPath('message', 'FAQ berhasil dibuat');

        $this->assertDatabaseHas('faqs', [
            'question' => 'Apa itu PTSA?',
            'answer' => 'Program Tahunan Serikat Pekerja.',
        ]);
    }

    public function test_store_requires_question_and_answer(): void
    {
        $this->postJson('/api/faqs', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['question', 'answer']);
    }

    public function test_show_returns_faq(): void
    {
        $faq = Faq::create(['question' => 'Apa itu PTSA?', 'answer' => 'Program Tahunan Serikat Pekerja.']);

        $this->getJson("/api/faqs/{$faq->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $faq->id)
            ->assertJsonPath('data.question', 'Apa itu PTSA?');
    }

    public function test_update_modifies_faq(): void
    {
        $faq = Faq::create(['question' => 'Apa itu PTSA?', 'answer' => 'Program Tahunan Serikat Pekerja.']);

        $this->putJson("/api/faqs/{$faq->id}", [
            'answer' => 'Program Tahunan Serikat Pekerja disingkat PTSA.',
        ])
            ->assertOk()
            ->assertJsonPath('data.answer', 'Program Tahunan Serikat Pekerja disingkat PTSA.')
            ->assertJsonPath('data.question', 'Apa itu PTSA?');

        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'answer' => 'Program Tahunan Serikat Pekerja disingkat PTSA.',
        ]);
    }

    public function test_destroy_deletes_faq(): void
    {
        $faq = Faq::create(['question' => 'Apa itu PTSA?', 'answer' => 'Program Tahunan Serikat Pekerja.']);

        $this->deleteJson("/api/faqs/{$faq->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }
}
