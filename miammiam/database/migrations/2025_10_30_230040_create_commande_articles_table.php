<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commande_articles', function (Blueprint $table) {
            $table->increments('id_commande_article');
            $table->integer('id_commande');
            $table->integer('id_menuitem');
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 10, 2);
            $table->decimal('sous_total', 10, 2)->nullable();
            $table->text('instructions')->nullable();
            $table->timestamp('date_ajout')->useCurrent();
            
            $table->foreign('id_commande')->references('id_commande')->on('commandes');
            $table->foreign('id_menuitem')->references('id_menuitem')->on('menu_item');
            $table->index('id_commande');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commande_articles');
    }
};
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commande_articles');
    }
};

