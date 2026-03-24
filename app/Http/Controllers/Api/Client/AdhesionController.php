<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\TraitsApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\JoinRequest;
use App\Models\User;
use App\Http\Requests\RequestRequest;
use App\Models\TontineRequest;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdhesionController extends Controller
{
    use TraitsApiResponseTrait;

    #[OA\Post(
        path: '/api/join-request',
        summary: "Demande d'adhésion à un groupe existant",
        description: "Permet à un utilisateur connecté d'envoyer une demande pour rejoindre une tontine via son ID.",
        tags: ['Demande'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tontine_group_id'],
                properties: [
                    new OA\Property(
                        property: 'tontine_group_id',
                        type: 'integer',
                        example: 1,
                        description: "L'ID du groupe de tontine à rejoindre"
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Demande enregistrée avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 10),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'tontine_group_id', type: 'integer', example: 1),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Utilisateur non authentifié'),
            new OA\Response(response: 409, description: 'Conflit : Demande déjà existante'),
            new OA\Response(response: 422, description: 'Erreur de validation (ID du groupe inexistant ou manquant)'),
        ]
    )]
    public function JoinExistGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tontine_group_id' => 'required|int|exists:tontine_groups,id'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse('Validation Error!', 422, $validator->errors());
        }
        if (!Auth::check()) {
            return $this->errorResponse('User unauthorized', 401);
        }
        $userId = Auth::id();

        // Cas de doublon
        $exists = JoinRequest::where('user_id', $userId)
            ->where('tontine_group_id', $request->tontine_group_id)
            ->exists();

        if ($exists) {
            return $this->errorResponse('Demande déjà envoyée', 409);
        }

        $join = JoinRequest::create([
            'user_id' => $userId,
            'tontine_group_id' => $request->tontine_group_id,
        ]);
        return $this->successResponse($join, 'Request successfully recorded.', 200);
    }

    #[OA\Post(
        path: '/api/ask-request',
        summary: 'Créer une nouvelle demande de tontine',
        description: "Nouvelle demande au cas où aucun groupe ne lui correspond",
        tags: ['Demande'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['contribution_amount', 'frequency', 'duration'],
                properties: [
                    new OA\Property(
                        property: 'contribution_amount',
                        type: 'number',
                        format: 'float',
                        example: 5000,
                        description: 'Montant de la cotisation (min: 1)'
                    ),
                    new OA\Property(
                        property: 'frequency',
                        type: 'string',
                        enum: ['daily', 'weekly', 'monthly'],
                        example: 'weekly',
                        description: 'Fréquence des versements'
                    ),
                    new OA\Property(
                        property: 'duration',
                        type: 'integer',
                        example: 12,
                        description: 'Durée en nombre de cycles (min: 1)'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Demande enregistrée avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 5),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'contribution_amount', type: 'number', example: 5000),
                                new OA\Property(property: 'frequency', type: 'string', example: 'weekly'),
                                new OA\Property(property: 'duration', type: 'integer', example: 12),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Utilisateur non autorisé'),
            new OA\Response(response: 422, description: 'Erreur de validation (données incorrectes)'),
        ]
    )]
    public function Request(RequestRequest $request)
    {
        $validateData = $request->validated();
        if (!Auth::check()) {
            return $this->errorResponse('User unauthorized', 401);
        }
        $userId = Auth::id();
        $validateData['user_id'] = $userId;
        $join = TontineRequest::create($validateData);
        return $this->successResponse($join, 'Request successfully recorded.', 200);
    }
}
