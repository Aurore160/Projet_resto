<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    // Vérifie que l'utilisateur connecté a le bon rôle
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        
        // Si pas d'utilisateur connecté
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié',
            ], 401);
        }
        
        // Si le rôle de l'utilisateur n'est pas dans la liste des rôles autorisés
        if (!in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé : vous n\'avez pas les permissions nécessaires',
                'role_requis' => $roles,
                'votre_role' => $user->role,
            ], 403);
        }
        
        return $next($request);
    }
}
