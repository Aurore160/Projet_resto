<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Gérer les requêtes OPTIONS (preflight) AVANT toute autre logique
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', 'http://localhost:5173')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }

        // Pour les requêtes API, toujours retourner du JSON
        if ($request->is('api/*')) {
            // Gérer les erreurs de connexion à la base de données
            if ($e instanceof \Illuminate\Database\QueryException || 
                $e instanceof \PDOException ||
                (str_contains($e->getMessage(), 'could not translate host name') ||
                 str_contains($e->getMessage(), 'Connection refused') ||
                 str_contains($e->getMessage(), 'Unknown host') ||
                 str_contains($e->getMessage(), 'Maximum execution time'))) {
                
                \Log::error('Erreur de connexion à la base de données', [
                    'message' => $e->getMessage(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Service temporairement indisponible. Problème de connexion à la base de données.',
                    'error' => config('app.debug') ? $e->getMessage() : 'Service temporairement indisponible',
                ], 503)
                ->header('Access-Control-Allow-Origin', 'http://localhost:5173')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Content-Type', 'application/json');
            }
            
            // Logger l'erreur
            \Log::error('Erreur API', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
            
            // Déterminer le code de statut HTTP
            $statusCode = 500;
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                $statusCode = 401;
            } elseif ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                $statusCode = 403;
            } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                $statusCode = 404;
            } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                $statusCode = 422;
            } elseif (method_exists($e, 'getStatusCode')) {
                $statusCode = $e->getStatusCode();
            }
            
            // Retourner une réponse JSON
            $response = response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Une erreur est survenue',
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : 'Une erreur est survenue',
            ], $statusCode);
            
            // Ajouter les en-têtes CORS
            $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:5173');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Content-Type', 'application/json');
            
            return $response;
        }

        return parent::render($request, $e);
    }
}
