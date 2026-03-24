<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'CrediFlow API',
    version: '1.0.0',
    description: "Documentation de l'API CrediFlow"
)]
#[OA\Server(
    url: 'https://apicrediflow.onrender.com',
    description: 'Serveur Production (Render)'
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Serveur Local'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
class OpenApi {}