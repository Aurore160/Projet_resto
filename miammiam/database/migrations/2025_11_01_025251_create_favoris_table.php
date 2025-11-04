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
        Schema::create('favoris', function (Blueprint $table) {
            // 1. Clé primaire (comme dans commande_articles)
            $table->increments('id_favori');
        
        // 2. Clés étrangères (références vers utilisateur et menu_item)
        $table->integer('id_utilisateur');
        $table->integer('id_menuitem');
        
        // 3. Timestamp pour savoir quand le favori a été ajouté
        $table->timestamp('date_ajout')->useCurrent();
        
        // 4. Contraintes de clés étrangères (intégrité référentielle)
        $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur');
        $table->foreign('id_menuitem')->references('id_menuitem')->on('menu_item');
        
        // 5. Index pour améliorer les performances de recherche
        $table->index('id_utilisateur');
        $table->index('id_menuitem');
        
        // 6. Contrainte unique : un utilisateur ne peut pas ajouter le même plat deux fois
        $table->unique(['id_utilisateur', 'id_menuitem'], 'favoris_unique_user_menu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favoris');
    }
};

     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favoris');
    }
};
