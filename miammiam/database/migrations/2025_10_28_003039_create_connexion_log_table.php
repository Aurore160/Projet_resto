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
        Schema::create('connexion_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('utilisateur_id')->nullable();
            $table->string('email');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->enum('statut', ['succes', 'echec']);
            $table->timestamp('created_at')->useCurrent();
            
            // Index pour optimiser les recherches
            $table->index('utilisateur_id');
            $table->index('email');
            $table->index('ip_address');
            $table->index('created_at');
            
            // Clé étrangère vers la table utilisateur
            $table->foreign('utilisateur_id')
                  ->references('id_utilisateur')
                  ->on('utilisateur')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connexion_log');
    }
};
