
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->dropColumn('email_verified_at');
            $table->dropColumn('password');
            $table->dropRememberToken();
            $table->uuid('public_id')->unique();
            $table->boolean('active')->default(true);
            $table->string('role')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->dropColumn('public_id');
            $table->dropColumn('active');
            $table->dropColumn('role');
        });
    }
};
