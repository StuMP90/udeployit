<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('branch')->nullable();
            $table->string('deployment_path')->nullable();
            $table->string('last_deployed_sha')->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->boolean('auto_deploy')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'server_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_servers');
    }
};
