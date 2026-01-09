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
        Schema::table('favorites', function (Blueprint $table) {
            $table->nullableMorphs('favoritable');
            $table->unsignedBigInteger('post_id')->nullable()->change();
        });

        // Migrate existing data
        DB::table('favorites')->update([
            'favoritable_type' => 'App\\Models\\Post',
            'favoritable_id'   => DB::raw('post_id')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->unsignedBigInteger('post_id')->nullable(false)->change();
            $table->dropMorphs('favoritable');
        });
    }
};
