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
        Schema::create('payment_configs', function (Blueprint $table) {
            $table->bigIncrements('id_payment_config');
            $table->string('provider')->default('easypay'); // Pour permettre plusieurs providers à l'avenir
            $table->enum('mode', ['sandbox', 'production'])->default('sandbox');
            $table->text('cid_encrypted'); // CID chiffré
            $table->text('publishable_key_encrypted'); // Clé publique chiffrée
            $table->boolean('active')->default(true);
            $table->integer('created_by')->nullable(); // ID de l'admin qui a créé la config
            $table->integer('updated_by')->nullable(); // ID de l'admin qui a modifié
            $table->text('notes')->nullable(); // Notes pour la traçabilité
            $table->timestamps();
            
            // Index pour récupérer rapidement la config active
            $table->index(['provider', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_configs');
    }
};
