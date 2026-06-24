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
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('national_code')->nullable()->unique()->after('last_name');
            $table->date('birth_date')->nullable()->after('national_code');
            $table->string('gender')->nullable()->after('birth_date');

            $table->string('national_card_image_path')->nullable()->after('gender');
            $table->string('face_image_path')->nullable()->after('national_card_image_path');

            $table->string('identity_status')->default('pending')->index()->after('face_image_path');
            $table->timestamp('identity_verified_at')->nullable()->after('identity_status');
            $table->json('identity_verification_result')->nullable()->after('identity_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'national_code',
                'birth_date',
                'gender',
                'national_card_image_path',
                'face_image_path',
                'identity_status',
                'identity_verified_at',
                'identity_verification_result',
            ]);
        });
    }
};
