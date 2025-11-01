<?php

namespace App\Console\Commands;

use App\Models\Utilisateur;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Crée le compte administrateur';

    public function handle()
    {
        $email = 'admin@miammiam.ci';
        $motDePasse = 'Admin123';

        // Vérifier si l'admin existe déjà
        $existingAdmin = Utilisateur::where('email', $email)->first();

        if ($existingAdmin) {
            // Mettre à jour le mot de passe si l'admin existe déjà
            $existingAdmin->mot_de_passe = Hash::make($motDePasse);
            $existingAdmin->role = 'admin';
            $existingAdmin->statut_compte = 'actif';
            $existingAdmin->save();

            $this->info("✓ Compte administrateur mis à jour avec succès !");
            $this->info("Email: {$email}");
            $this->info("Mot de passe: {$motDePasse}");
            return 0;
        }

        // Créer un nouvel admin
        $admin = Utilisateur::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => $email,
            'mot_de_passe' => Hash::make($motDePasse),
            'role' => 'admin',
            'statut_compte' => 'actif',
            'points_balance' => 0,
            'date_inscription' => now(),
        ]);

        $this->info("✓ Compte administrateur créé avec succès !");
        $this->info("Email: {$email}");
        $this->info("Mot de passe: {$motDePasse}");
        $this->info("ID: {$admin->id_utilisateur}");

        return 0;
    }
}
