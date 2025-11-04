<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reclamation', function (Blueprint $table) {
            // Clé primaire
            $table->increments('id_reclamation');
            
            // Clé étrangère vers utilisateur (nullable car peut être un visiteur non connecté)
            $table->integer('id_utilisateur')->nullable();
            
            // Clé étrangère vers commande (nullable, si la réclamation concerne une commande spécifique)
            $table->integer('id_commande')->nullable();
            
            // Informations de contact
            $table->string('nom', 255);
            $table->string('email', 255);
            $table->string('telephone', 50)->nullable();
            
            // Contenu de la réclamation
            $table->string('sujet', 255);
            $table->text('message');
            
            // Statut de traitement : en_attente, en_cours, resolu, ferme
            $table->string('statut', 50)->default('en_attente')
                ->comment('Statut: en_attente, en_cours, resolu, ferme');
            
            // Dates
            $table->timestamp('date_creation')->useCurrent();
            $table->timestamp('date_traitement')->nullable();
            
            // Réponse du service client (nullable)
            $table->text('reponse')->nullable();
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_utilisateur');
            $table->index('id_commande');
            $table->index('statut');
            $table->index('date_creation');
            $table->index('email');
            
            // Contraintes de clés étrangères
            $table->foreign('id_utilisateur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('set null'); // Ne pas supprimer la réclamation si l'utilisateur est supprimé
            
            $table->foreign('id_commande')
                ->references('id_commande')
                ->on('commandes')
                ->onDelete('set null'); // Ne pas supprimer la réclamation si la commande est supprimée
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamation');
    }
};
