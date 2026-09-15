<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ScalarDocsController
{
    /**
     * Display Scalar API documentation UI
     */
    public function index(): View
    {
        return view('scalar-docs');
    }

    /**
     * Return OpenAPI specification as JSON
     */
    public function openapi(): JsonResponse
    {
        $openapi = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Pengaduan API',
                'version' => '1.0.0',
                'description' => 'API untuk sistem pengaduan dan layanan',
            ],
            'servers' => [
                [
                    'url' => url('/api'),
                    'description' => 'API Server',
                ],
            ],
            'paths' => [
                '/admin-profiles' => [
                    'get' => [
                        'summary' => 'Get admin profiles',
                        'tags' => ['Admin'],
                        'security' => [['sanctum' => []]],
                        'responses' => [
                            '200' => [
                                'description' => 'List of admin profiles',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => ['type' => 'integer'],
                                                    'nama_lengkap' => ['type' => 'string'],
                                                    'nama_petugas' => ['type' => 'string'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '401' => [
                                'description' => 'Unauthorized',
                            ],
                        ],
                    ],
                ],
                '/surveys' => [
                    'post' => [
                        'summary' => 'Create a new survey',
                        'tags' => ['Survey'],
                        'security' => [['sanctum' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['complaint_id'],
                                        'properties' => [
                                            'complaint_id' => ['type' => 'integer'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Survey created successfully',
                            ],
                            '401' => [
                                'description' => 'Unauthorized',
                            ],
                        ],
                    ],
                ],
                '/surveys/{survey}' => [
                    'get' => [
                        'summary' => 'Get survey details',
                        'tags' => ['Survey'],
                        'parameters' => [
                            [
                                'name' => 'survey',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer'],
                                'description' => 'Survey ID',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Survey details',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'id' => ['type' => 'integer'],
                                                'complaint_id' => ['type' => 'integer'],
                                                'created_by_admin_id' => ['type' => 'integer'],
                                                'status' => ['type' => 'string'],
                                                'closed_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                                                'created_at' => ['type' => 'string', 'format' => 'date-time'],
                                                'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '404' => [
                                'description' => 'Survey not found',
                            ],
                        ],
                    ],
                ],
                '/surveys/{survey}/responses' => [
                    'post' => [
                        'summary' => 'Submit survey responses',
                        'tags' => ['Survey'],
                        'parameters' => [
                            [
                                'name' => 'survey',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer'],
                                'description' => 'Survey ID',
                            ],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['responses'],
                                        'properties' => [
                                            'responses' => [
                                                'type' => 'array',
                                                'items' => [
                                                    'type' => 'object',
                                                    'required' => ['survey_question_id', 'answer'],
                                                    'properties' => [
                                                        'survey_question_id' => ['type' => 'integer'],
                                                        'answer' => ['type' => 'string'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Responses submitted successfully',
                            ],
                            '404' => [
                                'description' => 'Survey not found',
                            ],
                        ],
                    ],
                ],
                '/surveys/{survey}/close' => [
                    'patch' => [
                        'summary' => 'Close a survey',
                        'tags' => ['Survey'],
                        'security' => [['sanctum' => []]],
                        'parameters' => [
                            [
                                'name' => 'survey',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer'],
                                'description' => 'Survey ID',
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Survey closed successfully',
                            ],
                            '401' => [
                                'description' => 'Unauthorized',
                            ],
                            '404' => [
                                'description' => 'Survey not found',
                            ],
                        ],
                    ],
                ],
                '/provinces' => [
                    'get' => [
                        'summary' => 'Get provinces',
                        'tags' => ['Wilayah'],
                        'responses' => [
                            '200' => [
                                'description' => 'List of provinces',
                            ],
                        ],
                    ],
                ],
                '/cities/{province_code}' => [
                    'get' => [
                        'summary' => 'Get cities by province',
                        'tags' => ['Wilayah'],
                        'parameters' => [
                            [
                                'name' => 'province_code',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string'],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'List of cities',
                            ],
                        ],
                    ],
                ],
                '/complaint-categories' => [
                    'get' => [
                        'summary' => 'Get complaint categories',
                        'tags' => ['Complaint'],
                        'responses' => [
                            '200' => [
                                'description' => 'List of complaint categories',
                            ],
                        ],
                    ],
                ],
                '/industrial-sectors' => [
                    'get' => [
                        'summary' => 'Get industrial sectors',
                        'tags' => ['Industrial'],
                        'responses' => [
                            '200' => [
                                'description' => 'List of industrial sectors',
                            ],
                        ],
                    ],
                ],
                '/complaints' => [
                    'post' => [
                        'summary' => 'Create a new complaint',
                        'tags' => ['Complaint'],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'complainant_id' => ['type' => 'integer'],
                                            'company_id' => ['type' => 'integer'],
                                            'category_id' => ['type' => 'integer'],
                                            'description' => ['type' => 'string'],
                                            'complaint_date' => ['type' => 'string', 'format' => 'date-time'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Complaint created',
                            ],
                        ],
                    ],
                    'get' => [
                        'summary' => 'Get all complaints',
                        'tags' => ['Complaint'],
                        'security' => [['sanctum' => []]],
                        'responses' => [
                            '200' => [
                                'description' => 'List of complaints',
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'sanctum' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'Authorization',
                    ],
                ],
            ],
        ];

        return response()->json($openapi);
    }
}
