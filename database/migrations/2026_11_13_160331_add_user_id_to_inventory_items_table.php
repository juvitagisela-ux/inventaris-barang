<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $ownerId = DB::table('users')->orderBy('id')->value('id');

        if ($ownerId !== null) {
            DB::table('inventory_items')->whereNull('user_id')->update(['user_id' => $ownerId]);
        }

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['user_id', 'code']);
            $table->index(['user_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'deleted_at']);
            $table->dropUnique(['user_id', 'code']);
            $table->unique('code');
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
