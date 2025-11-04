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
        Schema::table('commandes', function (Blueprint $table) {
            // Ajouter la colonne id_livreur (nullable car une commande peut ne pas avoir de livreur assigné)
            $table->integer('id_livreur')->nullable()->after('id_utilisateur');
            
            // Ajouter la contrainte de clé étrangère vers utilisateur
            $table->foreign('id_livreur')->references('id_utilisateur')->on('utilisateur');
            
            // Ajouter un index pour améliorer les performances de recherche par livreur
            $table->index('id_livreur');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['id_livreur']);
            
            // Supprimer l'index
            $table->dropIndex(['id_livreur']);
            
            // Supprimer la colonne
            $table->dropColumn('id_livreur');
        });
    }
};

    {
        Schema::table('commandes', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['id_livreur']);
            
            // Supprimer l'index
            $table->dropIndex(['id_livreur']);
            
            // Supprimer la colonne
            $table->dropColumn('id_livreur');
        });
    }
};
