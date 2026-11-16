<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reusable service catalog that proposals pull line items from.
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->json('deliverables')->nullable();
            $table->decimal('default_price', 10, 2)->default(0);
            $table->enum('billing_type', ['one_time', 'monthly', 'quarterly'])->default('monthly');
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('case_studies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('client_name')->nullable();
            $table->string('industry')->nullable();
            $table->text('summary')->nullable();
            $table->json('metrics')->nullable(); // [{label, value}]
            $table->string('image_path')->nullable();
            $table->text('testimonial_quote')->nullable();
            $table->string('testimonial_author')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('proposal_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->longText('executive_summary')->nullable();
            $table->longText('audit_report')->nullable();
            $table->json('roadmap_milestones')->nullable();
            $table->json('service_ids')->nullable();
            $table->json('case_study_ids')->nullable();
            $table->json('roi_defaults')->nullable();
            $table->unsignedTinyInteger('tenure_months')->default(3);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('proposal_templates');
        Schema::dropIfExists('case_studies');
        Schema::dropIfExists('services');
    }
};
