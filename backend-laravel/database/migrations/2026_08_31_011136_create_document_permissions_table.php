<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->enum('target_type', ['poste', 'service', 'user']);
            $table->unsignedBigInteger('target_id'); // ID du poste, service ou user ciblé
            $table->dateTime('expires_at')->nullable(); // Pour les accès temporaires
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_permissions');
    }
};
