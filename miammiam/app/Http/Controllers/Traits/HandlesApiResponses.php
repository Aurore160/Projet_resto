<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

trait HandlesApiResponses
{
    /**
     * Retourner une réponse JSON de succès
     * 
     * @param mixed $data Les données à retourner
     * @param string|null $message Le message de succès
     * @param int $statusCode Le code HTTP (par défaut 200)
     * @return JsonResponse
     */
    protected function successResponse($data = null, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = ['success' => true];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Retourner une réponse JSON d'erreur
     * 
     * @param string $message Le message d'erreur
     * @param int $statusCode Le code HTTP (par défaut 400)
     * @param mixed $errors Erreurs supplémentaires (validation, etc.)
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Gérer une exception et retourner une réponse d'erreur standardisée
     * 
     * @param Exception $e L'exception capturée
     * @param string $context Le contexte de l'erreur (pour le logging)
     * @param array $additionalData Données supplémentaires pour le log
     * @param bool $includeDetails Inclure les détails de l'exception dans la réponse (déconseillé en production)
     * @return JsonResponse
     */
    protected function handleException(
        Exception $e,
        string $context,
        array $additionalData = [],
        bool $includeDetails = false
    ): JsonResponse {
        // Logger l'erreur avec le contexte
        Log::error($context, array_merge([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], $additionalData));

        $response = [
            'success' => false,
            'message' => 'Erreur lors du traitement',
        ];

        // En développement, on peut inclure plus de détails
        if ($includeDetails && config('app.debug', false)) {
            $response['error'] = $e->getMessage();
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();
        }

        return response()->json($response, 500);
    }

    /**
     * Retourner une réponse "non trouvé" (404)
     * 
     * @param string $resource Le nom de la ressource (ex: "Commande", "Utilisateur")
     * @return JsonResponse
     */
    protected function notFoundResponse(string $resource = 'Ressource'): JsonResponse
    {
        return $this->errorResponse("{$resource} non trouvée", 404);
    }

    /**
     * Retourner une réponse "non autorisé" (403)
     * 
     * @param string $message Le message d'erreur
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Accès non autorisé'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    /**
     * Retourner une réponse "créé" (201)
     * 
     * @param mixed $data Les données créées
     * @param string|null $message Le message de succès
     * @return JsonResponse
     */
    protected function createdResponse($data = null, ?string $message = null): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }
}

