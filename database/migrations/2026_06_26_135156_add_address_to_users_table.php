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
        Schema::table('users', function (Blueprint $table) {
            // Match the unsigned integer primary keys of the iran_provinces / iran_cities tables.
            $table->unsignedInteger('province_id')->nullable()->after('gender');
            $table->unsignedInteger('city_id')->nullable()->after('province_id');
            $table->text('address')->nullable()->after('city_id');

            $table->foreign('province_id')->references('id')->on('iran_provinces')->nullOnDelete();
            $table->foreign('city_id')->references('id')->on('iran_cities')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropForeign(['city_id']);
            $table->dropColumn(['province_id', 'city_id', 'address']);
        });
    }
};
