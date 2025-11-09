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
        
        // Parser les rôles si plusieurs sont passés sous forme de chaîne (ex: "employe,gerant,admin")
        $rolesArray = [];
        foreach ($roles as $role) {
            // Si le rôle contient une virgule, c'est qu'on a passé plusieurs rôles en un seul paramètre
            if (strpos($role, ',') !== false) {
                $rolesArray = array_merge($rolesArray, explode(',', $role));
            } else {
                $rolesArray[] = $role;
            }
        }
        
        // Nettoyer les espaces
        $rolesArray = array_map('trim', $rolesArray);
        
        // Si le rôle de l'utilisateur n'est pas dans la liste des rôles autorisés
        if (!in_array($user->role, $rolesArray)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé : vous n\'avez pas les permissions nécessaires',
                'role_requis' => $rolesArray,
                'votre_role' => $user->role,
            ], 403);
        }
        
        return $next($request);
    }
}
