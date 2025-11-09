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
        Schema::create('messages', function (Blueprint $table) {
            // Clé primaire
            $table->increments('id_message');
            
            // Clé étrangère vers l'expéditeur (employé)
            $table->integer('id_expediteur');
            
            // Clé étrangère vers le destinataire (gérant)
            $table->integer('id_destinataire');
            
            // Sujet du message (type de souci)
            $table->string('sujet', 255);
            
            // Contenu du message
            $table->text('message');
            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
            
            // Type de message : 'signalement', 'question', 'urgence', 'retard', 'erreur', 'client_absent', 'autre'
            $table->string('type_message', 50)->default('signalement')
                ->comment('Type: signalement, question, urgence, retard, erreur, client_absent, autre');
            
            // Priorité : 'basse', 'normale', 'haute', 'urgente'
            $table->string('priorite', 20)->default('normale')
                ->comment('Priorité: basse, normale, haute, urgente');
            
            // Statut du message : 'envoye', 'lu', 'repondu', 'resolu'
            $table->string('statut', 20)->default('envoye')
                ->comment('Statut: envoye, lu, repondu, resolu');
            
            // Date d'envoi
            $table->timestamp('date_envoi')->useCurrent();
            
            // Date de lecture (nullable)
            $table->timestamp('date_lecture')->nullable();
            
            // Réponse du gérant (nullable)
            $table->text('reponse')->nullable();
            
            // Date de réponse (nullable)
            $table->timestamp('date_reponse')->nullable();
            
            // Index pour améliorer les performances
            $table->index('id_expediteur');
            $table->index('id_destinataire');
            $table->index('statut');
            $table->index('type_message');
            $table->index('priorite');
            $table->index('date_envoi');
            
            // Contraintes de clés étrangères
            $table->foreign('id_expediteur')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
            
            $table->foreign('id_destinataire')
                ->references('id_utilisateur')
                ->on('utilisateur')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
