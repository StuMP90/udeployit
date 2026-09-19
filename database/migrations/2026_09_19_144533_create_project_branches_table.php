<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('branch_name');
            $table->string('latest_sha');
            $table->timestamp('latest_committed_at')->nullable();
            $table->string('created_snapshot_sha');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'branch_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_branches');
    }
};
