<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "API Marimoi",
    version: "1.0.0",
    description: "Dokumentasi API Marimoi"
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Local Server"
)]
#[OA\Schema(
    schema: "User",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
        new OA\Property(property: "role_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "opd_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time")
    ]
)]
class OpenApi {}
