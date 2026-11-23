<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->string('category');
            $table->unsignedInteger('quantity');
            $table->string('unit', 40);
            $table->enum('condition', ['baik', 'rusak ringan', 'rusak berat']);
            $table->string('location');
            $table->date('acquired_at');
            $table->decimal('acquisition_cost', 15, 2);
            $table->string('acquisition_source');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
