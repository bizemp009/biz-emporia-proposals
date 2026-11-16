<?php

namespace Database\Seeders;

use App\Models\CaseStudy;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalTemplate;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@bizemporia.com');
        $password = env('ADMIN_PASSWORD', 'change-me-now');

        User::updateOrCreate(['email' => $email], [
            'name' => env('ADMIN_NAME', 'Biz Emporia Admin'),
            'password' => Hash::make($password),
        ]);
        $this->command?->info("Admin login: {$email} / {$password}  (set ADMIN_EMAIL / ADMIN_PASSWORD in .env before seeding production)");

        if (Service::exists()) {
            return; // catalog already seeded
        }

        // ------------------------------------------------------------ catalog
        $catalog = [
            ['Technical SEO Audit & Fixes', 'SEO', 'Crawlability, Core Web Vitals, indexation and schema fixes that unblock organic growth.', ['Full technical crawl & prioritised issue log', 'Core Web Vitals remediation plan', 'Schema markup implementation', 'XML sitemap & robots.txt hardening'], 45000, 'one_time', false],
            ['SEO / GEO Growth Retainer', 'SEO', 'Ongoing on-page optimisation, content and AI-search (GEO) visibility.', ['Keyword & topic cluster strategy', '4 optimised articles / month', 'On-page optimisation of 10 URLs / month', 'AI Overview & LLM citation tracking', 'Monthly ranking report'], 60000, 'monthly', false],
            ['Conversion Rate Optimisation (CRO)', 'CRO', 'Research-led experiments to turn more of your existing traffic into revenue.', ['Heatmap & session recording analysis', '2 A/B tests per month', 'Landing page copy & UX recommendations', 'Experiment results dashboard'], 40000, 'monthly', false],
            ['Google Ads Management', 'Paid Media', 'Search, Performance Max and Shopping campaign management.', ['Account restructure & conversion tracking', 'Weekly bid & budget optimisation', 'Ad copy testing', 'Negative keyword management'], 35000, 'monthly', false],
            ['Meta Ads Management', 'Paid Media', 'Prospecting and retargeting across Facebook & Instagram.', ['Audience & creative testing framework', 'Pixel / CAPI setup', 'Weekly optimisation', '4 static creatives / month'], 30000, 'monthly', true],
            ['Platform Optimisation (Shopify / WordPress)', 'Platform', 'Speed, UX and checkout improvements on your existing platform.', ['Theme performance optimisation', 'Checkout friction review', 'App / plugin audit & clean-up'], 75000, 'one_time', true],
            ['Lead Generation Funnel Build', 'Lead Gen', 'High-converting landing pages, lead magnets and CRM automation.', ['2 landing pages', 'Lead magnet design', 'CRM & email nurture automation (5 emails)', 'Lead scoring setup'], 90000, 'one_time', true],
            ['Analytics & Reporting Dashboard', 'Analytics', 'GA4, Looker Studio and server-side tracking you can trust.', ['GA4 audit & event setup', 'Looker Studio executive dashboard', 'Monthly insights call'], 15000, 'monthly', true],
            ['Quarterly Strategy Workshop', 'Strategy', 'Half-day leadership workshop to review results and re-plan.', ['Performance deep-dive', 'Competitive landscape review', 'Next-quarter roadmap'], 50000, 'quarterly', true],
        ];

        $services = collect($catalog)->map(fn ($s) => Service::create([
            'name' => $s[0], 'category' => $s[1], 'description' => $s[2], 'deliverables' => $s[3],
            'default_price' => $s[4], 'billing_type' => $s[5], 'is_optional' => $s[6],
        ]));

        // ------------------------------------------------------------ case studies
        $cases = collect([
            ['3.4× organic revenue for a D2C home décor brand', 'Nest & Nook', 'E-commerce', 'Technical SEO overhaul plus a topic-cluster content engine took organic from 18% to 41% of total revenue in two quarters.', [['value' => '+212%', 'label' => 'Organic sessions'], ['value' => '3.4×', 'label' => 'Organic revenue'], ['value' => '41%', 'label' => 'Revenue share']], 'They found problems three previous agencies missed — and fixed them in weeks.', 'Ananya R., Founder'],
            ['47% lower cost per lead for a B2B SaaS', 'LedgerFlow', 'B2B SaaS', 'Rebuilt Google Ads around high-intent keywords and launched a CRO programme on demo pages.', [['value' => '-47%', 'label' => 'Cost per lead'], ['value' => '+62%', 'label' => 'Demo bookings'], ['value' => '5.1×', 'label' => 'Pipeline ROAS']], 'Our sales team finally has more qualified demos than they can handle.', 'Rahul M., VP Growth'],
            ['₹2.1Cr in pipeline for a luxury real-estate developer', 'Aurum Estates', 'Real Estate', 'Lead-gen funnel with WhatsApp automation and Meta prospecting for premium launches.', [['value' => '1,840', 'label' => 'Qualified leads'], ['value' => '₹2.1Cr', 'label' => 'Pipeline'], ['value' => '90 days', 'label' => 'Time to result']], 'Every launch since has sold out faster.', 'Vikram S., Director'],
        ])->map(fn ($c) => CaseStudy::create([
            'title' => $c[0], 'client_name' => $c[1], 'industry' => $c[2], 'summary' => $c[3],
            'metrics' => $c[4], 'testimonial_quote' => $c[5], 'testimonial_author' => $c[6],
        ]));

        $roadmap = [
            ['title' => 'Month 1 · Audit & Foundations', 'items' => ['Technical & analytics audit', 'Tracking and conversion setup', 'Quick-win fixes shipped', 'Campaign restructure']],
            ['title' => 'Month 2 · Launch & Optimise', 'items' => ['Content engine live (4 articles)', 'First A/B tests launched', 'Paid campaigns optimised weekly']],
            ['title' => 'Month 3 · Scale', 'items' => ['Scale winning campaigns', 'Second round of CRO tests', 'Quarterly review & next-quarter roadmap']],
        ];

        $summary = '<div>Your brand has strong product-market fit and loyal repeat customers, but growth has plateaued because <strong>organic visibility and conversion efficiency</strong> are lagging behind competitors.</div><div>This proposal sets out a 90-day plan to fix the technical foundations, build a compounding SEO engine and turn more of your existing traffic into revenue.</div>';
        $audit = '<ul><li><strong>Core Web Vitals:</strong> LCP of 4.8s on mobile (target &lt; 2.5s) on key collection pages.</li><li><strong>Indexation:</strong> ~38% of product URLs are not indexed due to faceted-navigation duplication.</li><li><strong>Conversion:</strong> Checkout abandonment at 74%, above the 68% category benchmark.</li><li><strong>Tracking:</strong> GA4 purchase events are double-firing, inflating reported revenue.</li></ul>';

        ProposalTemplate::create([
            'name' => 'E-commerce SEO + CRO (90 days)',
            'description' => 'Technical SEO, content retainer and CRO with optional paid media.',
            'executive_summary' => $summary,
            'audit_report' => $audit,
            'roadmap_milestones' => $roadmap,
            'service_ids' => $services->whereIn('name', ['Technical SEO Audit & Fixes', 'SEO / GEO Growth Retainer', 'Conversion Rate Optimisation (CRO)', 'Meta Ads Management', 'Analytics & Reporting Dashboard'])->pluck('id')->values()->all(),
            'case_study_ids' => [$cases[0]->id, $cases[1]->id],
            'roi_defaults' => ['default_budget_or_spend' => 75000, 'default_working_hours' => 60, 'target_conversion_rate' => 2.2, 'projected_lift_multiplier' => 1.6, 'baseline_monthly_traffic' => 22000, 'cost_per_click' => 18, 'average_order_value' => 2400, 'hourly_rate' => 1500],
            'tenure_months' => 3,
        ]);

        // ------------------------------------------------------------ demo proposals
        $client = Client::create([
            'company_name' => 'Acme Retail Pvt Ltd', 'brand_name' => 'Acme Living', 'contact_person' => 'Priya Sharma',
            'email' => 'priya@acmeliving.example', 'website' => 'https://acmeliving.example', 'industry' => 'E-commerce',
        ]);

        $proposal = Proposal::create([
            'client_id' => $client->id,
            'title' => 'Growth Proposal for Acme Living',
            'slug' => 'biz-emporia-acme-living',
            'collaborator_name' => 'Srishti',
            'status' => 'sent',
            'sent_at' => now(),
            'executive_summary' => $summary,
            'audit_report' => $audit,
            'roadmap_milestones' => $roadmap,
            'tenure_months' => 3,
            'currency' => 'INR',
            'expires_at' => now()->addDays(30)->endOfDay(),
        ]);

        foreach (['Technical SEO Audit & Fixes', 'SEO / GEO Growth Retainer', 'Conversion Rate Optimisation (CRO)', 'Meta Ads Management', 'Platform Optimisation (Shopify / WordPress)', 'Analytics & Reporting Dashboard'] as $i => $name) {
            $s = $services->firstWhere('name', $name);
            $proposal->services()->create([
                'service_id' => $s->id, 'name' => $s->name, 'description' => $s->description, 'deliverables' => $s->deliverables,
                'price' => $s->default_price, 'billing_type' => $s->billing_type, 'is_optional' => $s->is_optional,
                'is_selected' => ! $s->is_optional || $name === 'Meta Ads Management', 'sort_order' => $i,
            ]);
        }
        $proposal->roiMetrics()->create(['default_budget_or_spend' => 75000, 'default_working_hours' => 60, 'target_conversion_rate' => 2.2, 'projected_lift_multiplier' => 1.6, 'baseline_monthly_traffic' => 22000, 'cost_per_click' => 18, 'average_order_value' => 2400, 'hourly_rate' => 1500]);
        $proposal->caseStudies()->sync([$cases[0]->id, $cases[1]->id]);
        $proposal->load('services');
        $proposal->update(['total_amount' => $proposal->pricing()['contract_value']]);
        $proposal->log('created');
        $proposal->log('sent');

        $client2 = Client::create([
            'company_name' => 'LedgerFlow Technologies', 'contact_person' => 'Rahul Mehta',
            'email' => 'rahul@ledgerflow.example', 'website' => 'https://ledgerflow.example', 'industry' => 'B2B SaaS',
        ]);
        $draft = Proposal::create([
            'client_id' => $client2->id, 'title' => 'Demand Generation Proposal for LedgerFlow', 'slug' => 'biz-emporia-ledgerflow',
            'collaborator_name' => 'Arjun', 'status' => 'draft', 'tenure_months' => 6, 'currency' => 'INR',
            'executive_summary' => '<div>A six-month demand generation programme to double qualified demo bookings.</div>',
            'roadmap_milestones' => [['title' => 'Months 1–2 · Foundations', 'items' => ['Tracking & CRM integration', 'Google Ads rebuild']], ['title' => 'Months 3–4 · Optimise', 'items' => ['Demo page CRO tests']], ['title' => 'Months 5–6 · Scale', 'items' => ['Expand to LinkedIn', 'Quarterly workshop']]],
            'expires_at' => now()->addDays(30)->endOfDay(),
        ]);
        foreach (['Google Ads Management', 'Lead Generation Funnel Build', 'Conversion Rate Optimisation (CRO)', 'Quarterly Strategy Workshop'] as $i => $name) {
            $s = $services->firstWhere('name', $name);
            $draft->services()->create([
                'service_id' => $s->id, 'name' => $s->name, 'description' => $s->description, 'deliverables' => $s->deliverables,
                'price' => $s->default_price, 'billing_type' => $s->billing_type, 'is_optional' => $s->is_optional, 'is_selected' => true, 'sort_order' => $i,
            ]);
        }
        $draft->roiMetrics()->create(['default_budget_or_spend' => 150000, 'default_working_hours' => 80, 'target_conversion_rate' => 3.5, 'projected_lift_multiplier' => 1.3, 'baseline_monthly_traffic' => 9000, 'cost_per_click' => 120, 'average_order_value' => 85000, 'hourly_rate' => 2000]);
        $draft->caseStudies()->sync([$cases[1]->id]);
        $draft->load('services');
        $draft->update(['total_amount' => $draft->pricing()['contract_value']]);
        $draft->log('created');
    }
}
