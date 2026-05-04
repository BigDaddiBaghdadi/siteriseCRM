<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audit_job_id')->nullable()->constrained()->nullOnDelete();
            $table->text('business_summary')->nullable();
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->unsignedTinyInteger('redesign_score')->nullable();
            $table->unsignedTinyInteger('mobile_score')->nullable();
            $table->unsignedTinyInteger('performance_score')->nullable();
            $table->unsignedTinyInteger('accessibility_score')->nullable();
            $table->unsignedTinyInteger('seo_score')->nullable();
            $table->json('issues_json')->nullable();
            $table->json('recommendations_json')->nullable();
            $table->json('technology_json')->nullable();
            $table->json('contact_json')->nullable();
            $table->string('desktop_screenshot_path')->nullable();
            $table->string('mobile_screenshot_path')->nullable();
            $table->timestamps();

            $table->index('redesign_score');
            $table->index('overall_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};

