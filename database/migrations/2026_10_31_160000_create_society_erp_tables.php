<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_land_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('category', 40);
            $table->unsignedInteger('area_acre')->default(0);
            $table->unsignedInteger('area_kanal')->default(0);
            $table->unsignedInteger('area_marla')->default(0);
            $table->unsignedInteger('area_sqft')->default(0);
            $table->decimal('land_area_marla', 18, 6)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'category']);
        });

        Schema::create('project_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sector')->nullable();
            $table->string('category', 40)->default('residential');
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'name']);
        });

        Schema::create('project_plots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_block_id')->nullable()->constrained('project_blocks')->nullOnDelete();
            $table->string('plot_number');
            $table->string('category', 40)->default('residential');
            $table->unsignedInteger('area_acre')->default(0);
            $table->unsignedInteger('area_kanal')->default(0);
            $table->unsignedInteger('area_marla')->default(0);
            $table->unsignedInteger('area_sqft')->default(0);
            $table->decimal('land_area_marla', 18, 6)->default(0);
            $table->string('status', 20)->default('available');
            $table->decimal('list_price', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'plot_number']);
            $table->index(['project_id', 'status']);
        });

        Schema::create('project_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('category', 80);
            $table->decimal('estimated_amount', 15, 2)->default(0);
            $table->foreignId('party_sub_category_id')->nullable()->constrained('party_sub_categories')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'category']);
        });

        Schema::create('development_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('package_type', 40)->default('infrastructure');
            $table->foreignId('contractor_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->decimal('estimated_amount', 15, 2)->default(0);
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->string('status', 20)->default('planned');
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('boq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('development_package_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->string('unit', 40)->nullable();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('unit_rate', 15, 2)->default(0);
            $table->decimal('estimated_amount', 15, 2)->default(0);
            $table->decimal('actual_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('development_progress_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('development_package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('progress_percent', 5, 2);
            $table->text('notes')->nullable();
            $table->date('logged_on');
            $table->timestamps();
        });

        Schema::create('plot_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_plot_id')->constrained('project_plots')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('booked');
            $table->date('booking_date');
            $table->decimal('total_price', 15, 2);
            $table->decimal('booking_amount', 15, 2)->default(0);
            $table->foreignId('booking_day_book_entry_id')->nullable()->constrained('day_book_entries')->nullOnDelete();
            $table->foreignId('transferred_from_booking_id')->nullable()->constrained('plot_bookings')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plot_booking_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('Standard');
            $table->unsignedInteger('installment_count')->default(1);
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('installment_dues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installment_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('label')->nullable();
            $table->date('due_date');
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->foreignId('day_book_entry_id')->nullable()->constrained('day_book_entries')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['installment_plan_id', 'sequence']);
        });

        Schema::create('society_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('module', 60);
            $table->string('action', 60);
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('request_notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'society_role')) {
                $table->string('society_role', 40)->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'society_role')) {
                $table->dropColumn('society_role');
            }
        });

        Schema::dropIfExists('society_approval_requests');
        Schema::dropIfExists('installment_dues');
        Schema::dropIfExists('installment_plans');
        Schema::dropIfExists('plot_bookings');
        Schema::dropIfExists('development_progress_logs');
        Schema::dropIfExists('boq_items');
        Schema::dropIfExists('development_packages');
        Schema::dropIfExists('project_budgets');
        Schema::dropIfExists('project_plots');
        Schema::dropIfExists('project_blocks');
        Schema::dropIfExists('project_land_allocations');
    }
};
