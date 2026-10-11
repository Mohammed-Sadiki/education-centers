<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->string('image_path')->nullable();
            $table->boolean('is_public')->default(true);
            $table->enum('status', ['draft','published','archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['center_id', 'status']);
            $table->index(['is_public', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('announcements'); }
};