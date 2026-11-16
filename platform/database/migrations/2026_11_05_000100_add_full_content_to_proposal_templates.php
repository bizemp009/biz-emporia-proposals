<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Templates now store everything a proposal has except the client details:
 * full service lines (custom prices, copy, custom items), screenshots, strategist,
 * currency, booking link, validity and a title pattern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposal_templates', function (Blueprint $table) {
            $table->string('title_pattern')->nullable()->after('description');  // e.g. "Growth Proposal for {client}"
            $table->string('collaborator_name')->nullable()->after('title_pattern');
            $table->json('audit_screenshots')->nullable()->after('audit_report'); // [{path, caption}]
            $table->json('service_lines')->nullable()->after('service_ids');      // [{service_id, name, description, deliverables[], price, billing_type, is_optional, is_selected}]
            $table->string('currency', 8)->nullable()->after('tenure_months');
            $table->string('booking_url')->nullable()->after('currency');
            $table->unsignedSmallInteger('validity_days')->nullable()->after('booking_url');
        });
    }

    public function down(): void
    {
        Schema::table('proposal_templates', function (Blueprint $table) {
            $table->dropColumn(['title_pattern', 'collaborator_name', 'audit_screenshots', 'service_lines', 'currency', 'booking_url', 'validity_days']);
        });
    }
};
