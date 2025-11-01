<?php

namespace App\Console\Commands;

use App\Models\Utilisateur;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateGerantUser extends Command
{
    protected $signature = 'gerant:create';

    protected $description = 'Crée le compte gérant';

    public function handle()
    {
        $nom = 'Martin';
        $prenom = 'Sophie';
        $email = 'gerant@miammiam.ci';
        $motDePasse = 'SecurePass123!';
        $telephone = '0612345678';

        // Vérifier si le gérant existe déjà
        $existingGerant = Utilisateur::where('email', $email)->first();

        if ($existingGerant) {
            // Mettre à jour le gérant s'il existe déjà
            $existingGerant->nom = $nom;
            $existingGerant->prenom = $prenom;
            $existingGerant->mot_de_passe = Hash::make($motDePasse);
            $existingGerant->telephone = $telephone;
            $existingGerant->role = 'gerant';
            $existingGerant->statut_compte = 'actif';
            $existingGerant->save();

            $this->info("✓ Compte gérant mis à jour avec succès !");
            $this->info("Nom: {$nom} {$prenom}");
            $this->info("Email: {$email}");
            $this->info("Téléphone: {$telephone}");
            $this->info("Mot de passe: {$motDePasse}");
            return 0;
        }

        // Créer un nouveau gérant
        $gerant = Utilisateur::create([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'mot_de_passe' => Hash::make($motDePasse),
            'telephone' => $telephone,
            'role' => 'gerant',
            'statut_compte' => 'actif',
            'points_balance' => 0,
            'date_inscription' => now(),
        ]);

        $this->info("✓ Compte gérant créé avec succès !");
        $this->info("Nom: {$nom} {$prenom}");
        $this->info("Email: {$email}");
        $this->info("Téléphone: {$telephone}");
        $this->info("Mot de passe: {$motDePasse}");
        $this->info("ID: {$gerant->id_utilisateur}");

        return 0;
    }
}
