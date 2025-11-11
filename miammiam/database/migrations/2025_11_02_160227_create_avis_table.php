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
        Schema::create('avis', function (Blueprint $table) {
            // Clé primaire
            $table->increments('id_avis');
            
            // Clés étrangères
            $table->integer('id_utilisateur');
            $table->integer('id_menuitem')->nullable(); // Nullable car un avis peut être sur le service (pas de plat)
            $table->integer('id_commande')->nullable(); // Pour lier l'avis à une commande spécifique
            
            // Type d'avis : 'plat' ou 'service'
            $table->string('type_avis', 20)->default('plat')
                ->comment('Type d\'avis: plat ou service');
            
            // Note sur 5
            $table->integer('note')->unsigned()
                ->comment('Note de 1 à 5');
            
            // Commentaire (optionnel)
            $table->text('commentaire')->nullable();
            
            // Timestamp
            $table->timestamp('date_creation')->useCurrent();
            
            // Contraintes de clés étrangères
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur');
            $table->foreign('id_menuitem')->references('id_menuitem')->on('menu_item')->onDelete('set null');
            $table->foreign('id_commande')->references('id_commande')->on('commandes')->onDelete('set null');
            
            // Index pour améliorer les performances
            $table->index('id_utilisateur');
            $table->index('id_menuitem');
            $table->index('id_commande');
            $table->index('type_avis');
            $table->index('date_creation');
            
            // Contrainte : la note doit être entre 1 et 5
            // Note: Cette contrainte sera ajoutée directement dans PostgreSQL si nécessaire
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};

                ->comment('Note de 1 à 5');
            
            // Commentaire (optionnel)
            $table->text('commentaire')->nullable();
            
            // Timestamp
            $table->timestamp('date_creation')->useCurrent();
            
            // Contraintes de clés étrangères
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur');
            $table->foreign('id_menuitem')->references('id_menuitem')->on('menu_item')->onDelete('set null');
            $table->foreign('id_commande')->references('id_commande')->on('commandes')->onDelete('set null');
            
            // Index pour améliorer les performances
            $table->index('id_utilisateur');
            $table->index('id_menuitem');
            $table->index('id_commande');
            $table->index('type_avis');
            $table->index('date_creation');
            
            // Contrainte : la note doit être entre 1 et 5
            // Note: Cette contrainte sera ajoutée directement dans PostgreSQL si nécessaire
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};

                ->comment('Note de 1 à 5');
            
            // Commentaire (optionnel)
            $table->text('commentaire')->nullable();
            
            // Timestamp
            $table->timestamp('date_creation')->useCurrent();
            
            // Contraintes de clés étrangères
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur');
            $table->foreign('id_menuitem')->references('id_menuitem')->on('menu_item')->onDelete('set null');
            $table->foreign('id_commande')->references('id_commande')->on('commandes')->onDelete('set null');
            
            // Index pour améliorer les performances
            $table->index('id_utilisateur');
            $table->index('id_menuitem');
            $table->index('id_commande');
            $table->index('type_avis');
            $table->index('date_creation');
            
            // Contrainte : la note doit être entre 1 et 5
            // Note: Cette contrainte sera ajoutée directement dans PostgreSQL si nécessaire
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};
                ->comment('Note de 1 à 5');
            
            // Commentaire (optionnel)
            $table->text('commentaire')->nullable();
            
            // Timestamp
            $table->timestamp('date_creation')->useCurrent();
            
            // Contraintes de clés étrangères
            $table->foreign('id_utilisateur')->references('id_utilisateur')->on('utilisateur');
            $table->foreign('id_menuitem')->references('id_menuitem')->on('menu_item')->onDelete('set null');
            $table->foreign('id_commande')->references('id_commande')->on('commandes')->onDelete('set null');
            
            // Index pour améliorer les performances
            $table->index('id_utilisateur');
            $table->index('id_menuitem');
            $table->index('id_commande');
            $table->index('type_avis');
            $table->index('date_creation');
            
            // Contrainte : la note doit être entre 1 et 5
            // Note: Cette contrainte sera ajoutée directement dans PostgreSQL si nécessaire
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};
