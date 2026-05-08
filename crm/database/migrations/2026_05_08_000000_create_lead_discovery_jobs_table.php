<?php

use App\Models\LeadDiscoveryJob;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_discovery_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('niche')->nullable();
            $table->boolean('random_niche')->default(false);
            $table->string('city');
            $table->string('country')->nullable();
            $table->unsignedSmallInteger('result_limit')->default(15);
            $table->string('target')->default(LeadDiscoveryJob::TARGET_NEEDS_REDESIGN);
            $table->string('status')->default(LeadDiscoveryJob::STATUS_QUEUED)->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('leads_found')->default(0);
            $table->text('last_error')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['city', 'niche']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_discovery_jobs');
    }
};
