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
        Schema::create('employe', function (Blueprint $table) {
            // Clé primaire
            $table->increments('id_employe');
            
            // Clé étrangère vers utilisateur (UNIQUE car un utilisateur ne peut avoir qu'un seul enregistrement employe)
            $table->integer('id_utilisateur')->unique();
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur');
            
            // Matricule unique
            $table->string('matricule', 20)->unique();
            
            // Salaire (optionnel, décimal)
            $table->decimal('salaire', 10, 2)->nullable()->default(null);
            
            // Date d'embauche (obligatoire)
            $table->date('date_embauche');
            
            // Date de fin de contrat (optionnelle)
            $table->date('date_fin_contrat')->nullable();
            
            // Statut (par défaut 'actif')
            $table->string('statut', 20)->default('actif');
            
            // Rôle spécifique (obligatoire)
            $table->string('role_specifique', 50);
            
            // Date de création
            $table->timestamp('date_creation')->useCurrent();
            
            // Index pour améliorer les performances
            $table->index('id_utilisateur');
            $table->index('matricule');
            $table->index('role_specifique');
            $table->index('statut');
        });
        
        // Ajouter les contraintes CHECK via DB::statement (Laravel ne supporte pas directement CHECK)
        // Note: Ces contraintes seront ajoutées directement dans PostgreSQL
        // car Laravel ne supporte pas nativement les CHECK constraints dans les migrations
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employe');
    }
};

            $table->date('date_embauche');
            
            // Date de fin de contrat (optionnelle)
            $table->date('date_fin_contrat')->nullable();
            
            // Statut (par défaut 'actif')
            $table->string('statut', 20)->default('actif');
            
            // Rôle spécifique (obligatoire)
            $table->string('role_specifique', 50);
            
            // Date de création
            $table->timestamp('date_creation')->useCurrent();
            
            // Index pour améliorer les performances
            $table->index('id_utilisateur');
            $table->index('matricule');
            $table->index('role_specifique');
            $table->index('statut');
        });
        
        // Ajouter les contraintes CHECK via DB::statement (Laravel ne supporte pas directement CHECK)
        // Note: Ces contraintes seront ajoutées directement dans PostgreSQL
        // car Laravel ne supporte pas nativement les CHECK constraints dans les migrations
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employe');
    }
};
