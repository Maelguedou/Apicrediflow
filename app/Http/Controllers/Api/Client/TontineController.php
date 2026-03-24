<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\TraitsApiResponseTrait;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Illuminate\support\Facades\Auth;
use App\Models\tontine_group;

class TontineController extends Controller
{
    use TraitsApiResponseTrait;

    #[OA\Get(
        path: '/api/Client_tontines',
        summary: "Liste de toutes les tontines",
        description: "Récupère les informations essentielles de tous les groupes de tontine disponibles.",
        tags: ['Tontines'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste récupérée avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'name', type: 'string', example: 'Solidarité Tech'),
                                    new OA\Property(property: 'contribution_amount', type: 'number', example: 10000),
                                    new OA\Property(property: 'frequency', type: 'string', example: 'monthly'),
                                    new OA\Property(property: 'duration', type: 'integer', example: 12),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401, 
                description: 'Utilisateur non authentifié'
            )
        ]
    )]
    public function getAll(){
        if(!Auth::check()){
            return $this->errorResponse('User unauthorized', 401);
        }
        $tontine=tontine_group::select('id','name','contribution_amount','frequency','duration')->get();
        return $this->successResponse($tontine, 'All tontine', 200);
    }

}
