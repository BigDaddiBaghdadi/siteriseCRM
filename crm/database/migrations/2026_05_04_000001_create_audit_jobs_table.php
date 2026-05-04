<?php

use App\Models\AuditJob;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default(AuditJob::STATUS_QUEUED)->index();
            $table->unsignedInteger('priority')->default(100)->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_jobs');
    }
};

