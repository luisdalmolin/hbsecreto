<?php

namespace App\OpenApi;

use OpenApi\Annotations\OpenApi as OpenApiDocument;
use OpenApi\Attributes as OA;

#[OA\OpenApi(
    openapi: OpenApiDocument::VERSION_3_1_0,
    info: new OA\Info(
        title: 'Hey Brother Secreto API',
        version: 'v1',
    ),
)]
#[OA\Components(
    securitySchemes: [
        new OA\SecurityScheme(
            securityScheme: 'bearerAuth',
            type: 'http',
            scheme: 'bearer',
            bearerFormat: 'Sanctum',
        ),
    ],
)]
final class OpenApiSpecification {}
