<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('collaborator_name')->nullable();
            $table->enum('status', ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired'])->default('draft');

            $table->longText('executive_summary')->nullable();
            $table->longText('audit_report')->nullable();
            $table->json('audit_screenshots')->nullable(); // [{path, caption}]

            $table->json('roadmap_milestones')->nullable(); // [{title, items[]}]
            $table->unsignedTinyInteger('tenure_months')->default(3);
            $table->string('currency', 8)->default('INR');
            $table->string('booking_url')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->longText('client_signature')->nullable(); // base64 PNG data URL
            $table->string('signer_name')->nullable();
            $table->string('signer_title')->nullable();
            $table->string('signer_ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('proposal_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('deliverables')->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('billing_type', ['one_time', 'monthly', 'quarterly'])->default('monthly');
            $table->boolean('is_optional')->default(false);
            $table->boolean('is_selected')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('proposal_roi_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('default_budget_or_spend', 10, 2)->default(1000.00); // monthly ad spend
            $table->decimal('default_working_hours', 8, 2)->default(40.00);     // monthly hours
            $table->decimal('target_conversion_rate', 5, 2)->default(2.50);     // percent
            $table->decimal('projected_lift_multiplier', 5, 2)->default(1.40);
            $table->unsignedInteger('baseline_monthly_traffic')->default(5000);  // organic visits
            $table->decimal('cost_per_click', 8, 2)->default(25.00);
            $table->decimal('average_order_value', 10, 2)->default(5000.00);
            $table->decimal('hourly_rate', 8, 2)->default(1500.00);
            $table->timestamps();
        });

        Schema::create('case_study_proposal', function (Blueprint $table) {
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_study_id')->constrained()->cascadeOnDelete();
            $table->primary(['proposal_id', 'case_study_id']);
        });

        Schema::create('proposal_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('event'); // created, sent, viewed, service_toggled, signed, rejected, pdf_downloaded, expired
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['proposal_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_activities');
        Schema::dropIfExists('case_study_proposal');
        Schema::dropIfExists('proposal_roi_metrics');
        Schema::dropIfExists('proposal_services');
        Schema::dropIfExists('proposals');
    }
};
