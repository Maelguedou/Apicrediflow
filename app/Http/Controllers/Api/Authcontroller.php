<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\TraitsApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\RegisterRequest;
use App\Mail\OptMail;
use OpenApi\Attributes as OA;

class Authcontroller extends Controller
{
    use TraitsApiResponseTrait;

    #[OA\Post(
        path: '/api/register',
        summary: "Inscription d'un client",
        tags: ['Authentification'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'company_name', type: 'string', example: 'Team_Oryx'),
                        new OA\Property(property: 'pseudo', type: 'string', example: 'Or229'),
                        new OA\Property(property: 'phone', type: 'string', example: '+2290165622300'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                        new OA\Property(property: 'activity_type', type: 'string', example: 'Commerce'),
                        new OA\Property(property: 'location', type: 'string', example: 'Cotonou'),
                        new OA\Property(property: 'registry', type: 'string', format: 'binary', description: 'Fichier du registre de commerce'),
                        new OA\Property(property: 'identity', type: 'string', format: 'binary', description: "Fichier de la pièce d'identité"),
                        new OA\Property(property: 'email', type: 'string', example: 'test@gmail.com'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User registered successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'company_name', type: 'string', example: 'Team_Oryx'),
                                new OA\Property(property: 'pseudo', type: 'string', example: 'Or229'),
                                new OA\Property(property: 'phone', type: 'string', example: '+2290165622300'),
                                new OA\Property(property: 'activity_type', type: 'string', example: 'Commerce'),
                                new OA\Property(property: 'location', type: 'string', example: 'Cotonou'),
                                new OA\Property(property: 'registry_url', type: 'string', example: 'http://localhost:8000/storage/registry/xyz.pdf'),
                                new OA\Property(property: 'identity_url', type: 'string', example: 'http://localhost:8000/storage/identity/xyz.pdf'),
                                new OA\Property(property: 'email', type: 'string', example: 'test@gmail.com'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation Error'),
        ]
    )]
    public function register(RegisterRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);
        $validatedData['role'] = 0;

        if ($request->hasFile('registry')) {
            $path = $request->file('registry')->store('registry', 'public');
            $validatedData['registry_url'] = $path;
        }
        if ($request->hasFile('identity')) {
            $path = $request->file('identity')->store('identity', 'public');
            $validatedData['identity_url'] = $path;
        }

        unset($validatedData['registry'], $validatedData['identity']);

        $user = User::create($validatedData);
        // Générer OTP
        $otp = rand(100000, 999999);
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(5);
        $user->save();

        // Envoyer OTP par email
        Mail::to($user->email)->send(new OptMail($otp));

        if ($user->registry_url) {
            $user->registry_url = asset('storage/' . $user->registry_url);
        }
        if ($user->identity_url) {
            $user->identity_url = asset('storage/' . $user->identity_url);
        }
        $data['token'] = $user->createToken('auth_token')->plainTextToken;
        $data['user'] = new UserResource($user);

        return $this->successResponse($data, 'User registered successfully', 201);
    }

    #[OA\Post(
        path: '/api/verify',
        summary: 'Vérification OTP',
        tags: ['Authentification'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id', 'otp'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'otp', type: 'integer', example: 124000),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Compte vérifié'),
            new OA\Response(response: 422, description: 'Erreur de validation ou OTP invalide'),
        ]
    )]
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'otp' => 'required|digits:6'
        ]);

        $user = User::find($request->id);

        if ($user->otp_code != $request->otp) {
            return $this->errorResponse('OTP invalide', 422);
        }

        if ($user->otp_expires_at < now()) {
            return $this->errorResponse('OTP expiré', 422);
        }

        $user->verified_at = now();
        $user->otp_code = null;
        $user->is_verified = true;
        $user->save();

        return $this->successResponse('Compte vérifié avec succès', 200);
    }

    #[OA\Post(
        path: '/api/Client_login',
        summary: 'Connexion du client',
        tags: ['Authentification'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['phone', 'password'],
                properties: [
                    new OA\Property(
                        property: 'phone',
                        type: 'string',
                        example: '+2290100112233',
                        description: 'Format: +22901 suivi de 8 à 15 chiffres'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        example: 'password123',
                        minLength: 8
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connexion réussie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Login successful'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'token', type: 'string', example: '1|abc123token...'),
                                new OA\Property(
                                    property: 'user',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'phone', type: 'string', example: '+2290100112233'),
                                        new OA\Property(property: 'is_verified', type: 'boolean', example: true),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Identifiants invalides (Téléphone ou mot de passe incorrect)'),
            new OA\Response(response: 403, description: 'Compte non vérifié'),
            new OA\Response(response: 422, description: 'Erreur de validation des champs'),
        ]
    )]
    public function Client_login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^\+22901[0-9]{8,15}$/',
            'password' => 'required|string|min:8'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse('Validation Error!', 422, $validator->errors());
        }
        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Invalid credential', 401);
        }
        if (!$user->is_verified) {
            return $this->errorResponse('Account not verified.', 403);
        }
        $user->tokens()->delete();

        $data['token'] = $user->createToken('auth_token')->plainTextToken;
        $data['user'] = $user;

        return $this->successResponse($data, 'Login successful', 200);
    }

    #[OA\Post(
        path: '/api/Client_logout',
        summary: 'Déconnexion du client',
        description: "Détruit le jeton d'accès actuel et déconnecte l'utilisateur.",
        tags: ['Authentification'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Déconnexion réussie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully'),
                        new OA\Property(property: 'data', type: 'string', nullable: true, example: null),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié (Token manquant ou invalide)'),
        ]
    )]
    public function Client_logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        $request->user()->tokens()->delete();
        return $this->successResponse(null, 'Logged out successfully');
    }
}
