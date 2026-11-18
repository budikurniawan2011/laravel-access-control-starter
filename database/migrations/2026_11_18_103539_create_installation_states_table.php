<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installation_states', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('setup_completed')->default(false);
            $table->timestamps();
        });

        DB::table('installation_states')->insert([
            'id' => 1,
            'setup_completed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_states');
    }
};
