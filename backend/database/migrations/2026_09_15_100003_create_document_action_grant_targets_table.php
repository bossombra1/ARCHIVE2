<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documents précis couverts par un grant à portée 'specific'
 * (document_action_grants.scope = 'specific'). Ignorée si le grant est à
 * portée 'all'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_action_grant_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grant_id')->constrained('document_action_grants')->onDelete('cascade');
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['grant_id', 'document_id'], 'doc_action_grant_target_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_action_grant_targets');
    }
};