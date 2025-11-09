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
        Schema::table('avis', function (Blueprint $table) {
            // Réponse du gérant (nullable)
            $table->text('reponse_gerant')->nullable()
                ->after('commentaire')
                ->comment('Réponse du gérant à l\'avis client');
            
            // Date de réponse du gérant (nullable)
            $table->timestamp('date_reponse')->nullable()
                ->after('reponse_gerant')
                ->comment('Date de réponse du gérant');
            
            // Statut de modération : 'approuve', 'en_attente', 'rejete'
            $table->string('statut_moderation', 20)->default('en_attente')
                ->after('date_reponse')
                ->comment('Statut de modération: approuve, en_attente, rejete');
            
            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};

            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};

            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};
            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};
            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};

            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};

            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};
            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};
            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};

            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};

            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};
            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};
            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};

            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};
            // Index pour améliorer les performances de filtrage
            $table->index('statut_moderation');
            $table->index('date_reponse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Supprimer les index
            $table->dropIndex(['statut_moderation']);
            $table->dropIndex(['date_reponse']);
            
            // Supprimer les colonnes
            $table->dropColumn(['reponse_gerant', 'date_reponse', 'statut_moderation']);
        });
    }
};
