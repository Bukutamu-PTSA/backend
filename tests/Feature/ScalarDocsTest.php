<?php

namespace Tests\Feature;

use Tests\TestCase;

class ScalarDocsTest extends TestCase
{
    /**
     * Test that /api/docs endpoint returns Scalar UI view
     */
    public function test_scalar_docs_index_returns_view(): void
    {
        $response = $this->get('/api/docs');
        
        $response->assertStatus(200);
        $response->assertViewIs('scalar-docs');
    }

    /**
     * Test that /api/docs/openapi endpoint returns valid OpenAPI JSON
     */
    public function test_scalar_docs_openapi_returns_json(): void
    {
        $response = $this->get('/api/docs/openapi');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        
        $data = $response->json();
        
        // Verify OpenAPI structure
        $this->assertArrayHasKey('openapi', $data);
        $this->assertArrayHasKey('info', $data);
        $this->assertArrayHasKey('paths', $data);
        $this->assertArrayHasKey('servers', $data);
        
        // Verify info structure
        $this->assertEquals('Pengaduan API', $data['info']['title']);
        $this->assertEquals('1.0.0', $data['info']['version']);
        
        // Verify endpoints are documented
        $this->assertArrayHasKey('/provinces', $data['paths']);
        $this->assertArrayHasKey('/complaints', $data['paths']);
        $this->assertArrayHasKey('/complaint-categories', $data['paths']);
    }

    /**
     * Test that /api/docs/openapi contains complaints endpoint
     */
    public function test_openapi_documents_complaints_endpoint(): void
    {
        $response = $this->get('/api/docs/openapi');
        $data = $response->json();
        
        // Verify complaints endpoints
        $this->assertArrayHasKey('post', $data['paths']['/complaints']);
        $this->assertArrayHasKey('get', $data['paths']['/complaints']);
        
        // Verify POST complaints has requestBody
        $this->assertArrayHasKey('requestBody', $data['paths']['/complaints']['post']);
    }
}
