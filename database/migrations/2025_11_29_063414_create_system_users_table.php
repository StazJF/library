<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_users')) {
            Schema::table('system_users', function (Blueprint $table) {
                if (!Schema::hasColumn('system_users', 'name')) {
                    $table->string('name')->nullable();
                }

                if (
                    !Schema::hasColumn('system_users', 'created_at') &&
                    !Schema::hasColumn('system_users', 'updated_at')
                ) {
                    $table->timestamps();
                } else {
                    if (!Schema::hasColumn('system_users', 'created_at')) {
                        $table->timestamp('created_at')->nullable();
                    }

                    if (!Schema::hasColumn('system_users', 'updated_at')) {
                        $table->timestamp('updated_at')->nullable();
                    }
                }

                if (!Schema::hasColumn('system_users', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            return;
        }

        Schema::create('system_users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->string('password');
            $table->string('role');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_users');
    }
};
