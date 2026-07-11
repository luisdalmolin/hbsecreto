<?php

use OpenApi\Annotations\OpenApi as OpenApiDocument;
use OpenApi\Generator;

test('generates a valid OpenAPI 3.1 document with bearer authentication', function () {
    $openApi = (new Generator)
        ->setVersion(OpenApiDocument::VERSION_3_1_0)
        ->generate([dirname(__DIR__, 2).'/app']);

    $document = json_decode($openApi->toJson(), true, 512, JSON_THROW_ON_ERROR);

    expect($openApi->validate(version: OpenApiDocument::VERSION_3_1_0))->toBeTrue()
        ->and($document['openapi'])->toBe(OpenApiDocument::VERSION_3_1_0)
        ->and($document['info'])->toMatchArray([
            'title' => 'Hey Brother Secreto API',
            'version' => 'v1',
        ])
        ->and($document['components']['securitySchemes']['bearerAuth'])->toMatchArray([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'Sanctum',
        ])
        ->and($document['paths'])->toHaveKeys([
            '/api/v1/auth/login',
            '/api/v1/auth/logout',
            '/api/v1/me',
        ])
        ->and($document['components']['schemas'])->toHaveKeys([
            'Authentication',
            'Error',
            'LoginRequest',
            'User',
        ]);
});
