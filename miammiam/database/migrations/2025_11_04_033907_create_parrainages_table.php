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
        Schema::create('parrainages', function (Blueprint $table) {
            // Clé primaire
            $table->increments('id_parrainage');
            
            // Clé étrangère vers le parrain (utilisateur qui a parrainé)
            $table->integer('id_parrain');
            
            // Clé étrangère vers le filleul (utilisateur parrainé)
            $table->integer('id_filleul');
            
            // Code de parrainage utilisé lors de l'inscription
            $table->string('code_parrainage_utilise', 10);
            
            // Date du parrainage (date d'inscription du filleul)
            $table->timestamp('date_parrainage')->useCurrent();
            
            // Indique si la première commande a été faite (pour attribuer les 20 points)
            $table->boolean('premiere_commande_faite')->default(false)
                ->comment('Indique si les 20 points de première commande ont été attribués');
            
            // Date de la première commande (nullable jusqu'à ce qu'elle soit faite)
            $table->timestamp('date_premiere_commande')->nullable()
                ->comment('Date de la première commande du filleul');
            
            // Points gagnés à l'inscription (10 points)
            $table->integer('points_inscription')->default(10)
                ->comment('Points attribués au parrain lors de l\'inscription du filleul');
            
            // Points gagnés à la première commande (20 points, nullable)
            $table->integer('points_premiere_commande')->nullable()
                ->comment('Points attribués au parrain lors de la première commande du filleul');
            
            // Index pour améliorer les performances
            $table->index('id_parrain');
            $table->index('id_filleul');
            $table->index('code_parrainage_utilise');
            $table->index('date_parrainage');
            $table->index('premiere_commande_faite');
            
            // Contraintes de clés étrangères
            $table->foreign('id_parrain')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade'); // Si le parrain est supprimé, supprimer les parrainages
            
            $table->foreign('id_filleul')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade'); // Si le filleul est supprimé, supprimer le parrainage
            
            // Contrainte d'unicité : un filleul ne peut avoir qu'un seul parrain
            $table->unique('id_filleul');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parrainages');
    }
};

            
            // Indique si la première commande a été faite (pour attribuer les 20 points)
            $table->boolean('premiere_commande_faite')->default(false)
                ->comment('Indique si les 20 points de première commande ont été attribués');
            
            // Date de la première commande (nullable jusqu'à ce qu'elle soit faite)
            $table->timestamp('date_premiere_commande')->nullable()
                ->comment('Date de la première commande du filleul');
            
            // Points gagnés à l'inscription (10 points)
            $table->integer('points_inscription')->default(10)
                ->comment('Points attribués au parrain lors de l\'inscription du filleul');
            
            // Points gagnés à la première commande (20 points, nullable)
            $table->integer('points_premiere_commande')->nullable()
                ->comment('Points attribués au parrain lors de la première commande du filleul');
            
            // Index pour améliorer les performances
            $table->index('id_parrain');
            $table->index('id_filleul');
            $table->index('code_parrainage_utilise');
            $table->index('date_parrainage');
            $table->index('premiere_commande_faite');
            
            // Contraintes de clés étrangères
            $table->foreign('id_parrain')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade'); // Si le parrain est supprimé, supprimer les parrainages
            
            $table->foreign('id_filleul')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade'); // Si le filleul est supprimé, supprimer le parrainage
            
            // Contrainte d'unicité : un filleul ne peut avoir qu'un seul parrain
            $table->unique('id_filleul');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parrainages');
    }
};
