<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('default_deployment_path')->nullable();
            $table->timestamps();

            $table->unique(['project_template_id', 'server_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_servers');
    }
};
