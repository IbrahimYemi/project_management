<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('task_image')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->foreignUuid('status_id')->constrained('project_task_statuses')->onDelete('cascade');
            $table->foreignUuid('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignUuid('assigned_to')->constrained('users')->onDelete('cascade');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->foreignUuid('priority_id')->constrained('priority_statuses')->onDelete('cascade')->default('default');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
