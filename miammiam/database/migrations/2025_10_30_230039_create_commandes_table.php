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
        Schema::create('commandes', function (Blueprint $table) {
            $table->increments('id_commande');
            $table->integer('id_utilisateur');
            $table->string('numero_commande', 20)->unique();
            $table->string('statut', 20)->default('en_attente');
            $table->string('type_commande', 20);
            $table->timestamp('heure_arrivee_prevue')->nullable();
            $table->text('adresse_livraison')->nullable();
            $table->decimal('montant_total', 10, 2);
            $table->integer('points_utilises')->default(0);
            $table->decimal('reduction_points', 10, 2)->default(0);
            $table->decimal('frais_livraison', 10, 2)->default(0);
            $table->timestamp('date_commande')->useCurrent();
            $table->timestamp('date_modification')->useCurrent()->useCurrentOnUpdate();
            $table->text('commentaire')->nullable();
            $table->text('instructions_speciales')->nullable();
            
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur');
            $table->index('id_utilisateur');
            $table->index('statut');
            $table->index('date_commande');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
