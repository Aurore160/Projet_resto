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
        Schema::create('menu_item', function (Blueprint $table) {
            $table->increments('id_menuitem');
            $table->integer('id_categorie');
            $table->string('nom', 100);
            $table->text('description')->nullable();
            $table->decimal('prix', 10, 2);
            $table->boolean('statut_disponibilite')->default(true);
            $table->string('photo_url', 255)->nullable();
            $table->boolean('plat_du_jour')->default(false);
            $table->integer('temps_preparation')->nullable();
            $table->text('ingredients')->nullable();
            $table->timestamp('date_creation')->useCurrent();
            $table->timestamp('date_modification')->useCurrent()->useCurrentOnUpdate();
            
            $table->foreign('id_categorie')->references('id_categorie')->on('categories');
            $table->index('id_categorie');
            $table->check(['prix >= 0']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_item');
    }
};
