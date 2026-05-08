<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table): void {
            $table->json('redesign_concept_json')->nullable()->after('contact_json');
            $table->string('redesign_mockup_path')->nullable()->after('mobile_screenshot_path');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table): void {
            $table->dropColumn(['redesign_concept_json', 'redesign_mockup_path']);
        });
    }
};
