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
        Schema::create('notifications', function (Blueprint $table) {
            // Clé primaire
            $table->increments('id_notification');
            
            // Clé étrangère vers utilisateur
            $table->integer('id_utilisateur');
            
            // Clé étrangère vers commande (nullable car certaines notifications peuvent être générales)
            $table->integer('id_commande')->nullable();
            
            // Type de notification : 'commande', 'system', 'promotion', etc.
            $table->string('type_notification', 50)->default('commande')
                ->comment('Type de notification: commande, system, promotion');
            
            // Titre de la notification
            $table->string('titre', 255);
            
            // Message de la notification
            $table->text('message');
            
            // Statut de lecture
            $table->boolean('lu')->default(false)
                ->comment('Indique si la notification a été lue');
            
            // Date de création
            $table->timestamp('date_creation')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_utilisateur');
            $table->index('id_commande');
            $table->index('lu');
            $table->index('date_creation');
            
            // Contraintes de clés étrangères
            $table->foreign('id_utilisateur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_commande')
                ->references('id_commande')
                ->on('commandes')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

