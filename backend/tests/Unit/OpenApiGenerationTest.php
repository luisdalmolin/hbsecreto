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
            'title' => 'CPX Secreto API',
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
            '/api/v1/dashboard',
            '/api/v1/notification-preferences',
            '/api/v1/notifications',
            '/api/v1/notifications/read',
            '/api/v1/notifications/{notification}/read',
            '/api/v1/push-devices',
            '/api/v1/push-devices/{pushDevice}',
            '/api/v1/groups',
            '/api/v1/groups/{group}',
            '/api/v1/groups/{group}/members',
            '/api/v1/groups/{group}/members/{member}',
            '/api/v1/groups/{group}/members/{member}/invite',
            '/api/v1/invitations/{token}',
            '/api/v1/invitations/{token}/claim',
            '/api/v1/groups/{group}/editions',
            '/api/v1/groups/{group}/editions/{edition}',
            '/api/v1/groups/{group}/editions/{edition}/participants',
            '/api/v1/groups/{group}/editions/{edition}/open',
            '/api/v1/groups/{group}/editions/{edition}/reveal',
            '/api/v1/groups/{group}/editions/{edition}/archive',
            '/api/v1/groups/{group}/editions/{edition}/draw-constraints',
            '/api/v1/groups/{group}/editions/{edition}/draw-constraints/copy-from-previous',
            '/api/v1/groups/{group}/editions/{edition}/draw-constraints/{drawConstraint}',
            '/api/v1/groups/{group}/editions/{edition}/draw/preflight',
            '/api/v1/groups/{group}/editions/{edition}/draw',
            '/api/v1/groups/{group}/editions/{edition}/my-assignment',
            '/api/v1/groups/{group}/editions/{edition}/assignments',
            '/api/v1/groups/{group}/editions/{edition}/conversations',
            '/api/v1/groups/{group}/editions/{edition}/conversations/{conversation}/messages',
            '/api/v1/groups/{group}/editions/{edition}/conversations/{conversation}/read',
            '/api/v1/groups/{group}/editions/{edition}/products/search',
            '/api/v1/groups/{group}/editions/{edition}/my-wishes',
            '/api/v1/groups/{group}/editions/{edition}/my-wishes/order',
            '/api/v1/groups/{group}/editions/{edition}/my-wishes/{wish}',
            '/api/v1/groups/{group}/editions/{edition}/pick-orders',
            '/api/v1/orders',
            '/api/v1/orders/{order}',
            '/api/v1/orders/{order}/refund',
            '/api/v1/payments/mercadopago/webhook',
        ])
        ->and($document['components']['schemas'])->toHaveKeys([
            'Authentication',
            'Dashboard',
            'DashboardEdition',
            'DashboardGroup',
            'CopiedDrawConstraints',
            'Error',
            'LoginRequest',
            'NotificationPreferences',
            'UpdateNotificationPreferencesRequest',
            'User',
            'UpdateUserRequest',
            'Group',
            'GroupCollection',
            'GroupMember',
            'IssuedInvitation',
            'InvitationPreview',
            'Edition',
            'EditionCollection',
            'EditionParticipant',
            'EditionParticipantCollection',
            'PaginationMeta',
            'CreateDrawConstraintRequest',
            'DrawConstraint',
            'DrawConstraintCollection',
            'DrawPreflight',
            'DrawReceipt',
            'AssignmentParticipant',
            'MyAssignment',
            'Assignment',
            'AssignmentCollection',
            'Conversation',
            'ConversationCollection',
            'ConversationCounterpart',
            'ConversationThread',
            'CreateMessageRequest',
            'MarkConversationReadRequest',
            'Message',
            'MessageAuthor',
            'Product',
            'ProductCollection',
            'CreateWishRequest',
            'UpdateWishRequest',
            'ReorderWishesRequest',
            'Wish',
            'WishCollection',
            'CreatePickOrderRequest',
            'MercadoPagoWebhook',
            'MercadoPagoWebhookResource',
            'Order',
            'OrderCollection',
        ])
        ->and($document['components']['schemas']['Edition']['properties']['eventDate'])->toMatchArray([
            'oneOf' => [
                ['type' => 'string', 'format' => 'date'],
                ['type' => 'null'],
            ],
        ])
        ->and($document['components']['schemas']['Edition']['properties']['drawnAt'])->toMatchArray([
            'oneOf' => [
                ['type' => 'string', 'format' => 'date-time'],
                ['type' => 'null'],
            ],
        ])
        ->and($document['components']['schemas']['Edition']['properties']['revealedAt'])->toMatchArray([
            'oneOf' => [
                ['type' => 'string', 'format' => 'date-time'],
                ['type' => 'null'],
            ],
        ])
        ->and($document['components']['schemas']['EditionParticipantCollection']['required'])->toContain('currentParticipantId')
        ->and($document['components']['schemas']['EditionParticipantCollection']['properties']['currentParticipantId'])->toMatchArray([
            'oneOf' => [
                ['type' => 'integer'],
                ['type' => 'null'],
            ],
        ])
        ->and($document['components']['schemas']['DashboardEdition']['properties'])->toHaveKeys([
            'isAdmin',
            'isParticipant',
            'assignmentAvailable',
        ])
        ->and($document['components']['schemas']['Order']['properties'])->toHaveKeys([
            'id',
            'editionId',
            'receiverParticipantId',
        ])
        ->and($document['components']['schemas']['Order']['required'])->toContain('receiverParticipantId')
        ->and($document['paths']['/api/v1/orders']['get']['parameters'])->toContainEqual([
            'name' => 'filter[edition_id]',
            'in' => 'query',
            'schema' => ['type' => 'integer', 'minimum' => 1],
        ])->toContainEqual([
            'name' => 'page',
            'in' => 'query',
            'schema' => ['type' => 'integer', 'minimum' => 1],
        ]);
});
