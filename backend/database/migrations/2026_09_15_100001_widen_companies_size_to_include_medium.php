<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Élargit companies.size pour accepter 'medium' (moyenne entreprise), en plus
 * de 'small' et 'large' déjà existants.
 *
 * On abandonne l'ENUM natif au profit d'un VARCHAR(20) validé côté
 * application (Rule::in dans les Requests), pour rester portable entre
 * MySQL (prod, cf. .env.example) et SQLite (tests, cf. phpunit.xml) :
 * modifier la liste de valeurs d'un ENUM MySQL nécessite du SQL brut, et
 * SQLite matérialise enum() sous forme de contrainte CHECK qu'on ne peut
 * pas altérer in-place sans recréer la colonne.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // Recrée la colonne pour supprimer la contrainte CHECK héritée
            // de l'ENUM d'origine (small, large uniquement).
            Schema::table('companies', function (Blueprint $table) {
                $table->string('size_new', 20)->nullable()->after('size');
            });
            DB::statement('UPDATE companies SET size_new = size');
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('size');
            });
            Schema::table('companies', function (Blueprint $table) {
                $table->renameColumn('size_new', 'size');
            });
        } else {
            // MySQL / MariaDB (SGBD de production de ce projet).
            DB::statement("ALTER TABLE companies MODIFY COLUMN size VARCHAR(20) NOT NULL");
        }
    }

    public function down(): void
    {
        // On ne revient pas à un ENUM strict pour éviter de perdre les
        // entreprises déjà passées en 'medium'. Un rollback complet
        // nécessiterait de les réassigner manuellement à 'small'/'large'.
        DB::statement("UPDATE companies SET size = 'large' WHERE size = 'medium'");
    }
};