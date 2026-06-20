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
            if (! Schema::hasColumn('users', 'newsletter_subscribed_at')) {
                // Null means the user is not subscribed to the SMS newsletter.
                $table->timestamp('newsletter_subscribed_at')->nullable()->after('phone_verified_at');
                $table->index('newsletter_subscribed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'newsletter_subscribed_at')) {
                $table->dropIndex(['newsletter_subscribed_at']);
                $table->dropColumn('newsletter_subscribed_at');
            }
        });
    }
};
