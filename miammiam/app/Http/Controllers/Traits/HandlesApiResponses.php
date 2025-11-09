<?php

namespace App\Http\Controllers\Traits;

trait HandlesApiResponses
{
    /**
     * Retourner une réponse de succès formatée
     * 
     * @param mixed $data Les données à retourner
     * @param string|null $message Le message de succès
     * @param int $statusCode Le code de statut HTTP (défaut: 200)
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data = null, $message = null, $statusCode = 200)
    {
        $response = [
            'success' => true,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Retourner une réponse d'erreur formatée
     * 
     * @param string $message Le message d'erreur
     * @param int $statusCode Le code de statut HTTP (défaut: 400)
     * @param mixed $errors Les erreurs détaillées (optionnel)
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse($message, $statusCode = 400, $errors = null)
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
     * Retourner une réponse "non trouvé" formatée
     * 
     * @param string $resource Le nom de la ressource non trouvée
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundResponse($resource = 'Ressource')
    {
        return $this->errorResponse(
            "{$resource} non trouvé(e)",
            404
        );
    }

    /**
     * Gérer une exception et retourner une réponse formatée
     * 
     * @param \Exception $e L'exception à gérer
     * @param string $message Le message d'erreur personnalisé
     * @param array|null $context Le contexte additionnel pour le logging
     * @param bool $includeDetails Si true, inclut les détails de l'erreur en mode debug
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleException(\Exception $e, $message = 'Une erreur est survenue', $context = null, $includeDetails = false)
    {
        // Logger l'erreur
        \Log::error($message, [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'context' => $context,
        ]);

        // Préparer la réponse
        $response = [
            'success' => false,
            'message' => $message,
        ];

        // Inclure les détails si demandé et en mode debug
        if ($includeDetails && config('app.debug')) {
            $response['error'] = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        } elseif (config('app.debug')) {
            $response['error'] = $e->getMessage();
        }

        // Déterminer le code de statut HTTP approprié
        $statusCode = 500;
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            $statusCode = 404;
        } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
            $statusCode = 422;
        } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
            $statusCode = 401;
        } elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            $statusCode = 403;
        } elseif (method_exists($e, 'getStatusCode')) {
            $statusCode = $e->getStatusCode();
        }

        return response()->json($response, $statusCode);
    }
}


