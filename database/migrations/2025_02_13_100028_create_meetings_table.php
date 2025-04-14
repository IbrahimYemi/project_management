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
        Schema::create('meetings', function (Blueprint $table) {
            $table->uuid('id')->primary()->unique();
            $table->string('agenda'); // Agenda for the meeting
            $table->string('link')->nullable(); // Meeting link (e.g., Zoom, Google Meet)
            $table->foreignUuid('project_id')->constrained('projects')->onDelete('cascade'); // Associated project
            $table->foreignUuid('team_id')->nullable()->constrained('teams')->onDelete('cascade'); // Associated team
            $table->foreignUuid('task_id')->nullable()->constrained('tasks')->onDelete('cascade'); // Optional: Associated task
            $table->date('date'); // Date of the meeting
            $table->time('time'); // Time of the meeting
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
