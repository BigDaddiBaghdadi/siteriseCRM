<?php

use App\Models\Lead;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->string('business_name');
            $table->string('category')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('website_url');
            $table->string('source')->nullable();
            $table->string('source_url')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default(Lead::STATUS_NEW)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['city', 'category']);
            $table->index('website_url');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};

