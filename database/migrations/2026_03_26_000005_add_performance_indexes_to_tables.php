<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'name'], 'users_role_name_index');
            $table->index('referred_by', 'users_referred_by_index');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->index('plan_group', 'plans_plan_group_index');
            $table->index('is_default', 'plans_is_default_index');
            $table->index(
                ['sort_order', 'price', 'id'],
                'plans_sort_price_id_index',
            );
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->index(
                ['user_id', 'status', 'end_date'],
                'user_subscriptions_user_status_end_index',
            );
            $table->index(
                ['status', 'end_date'],
                'user_subscriptions_status_end_index',
            );
            $table->index(
                ['plan_id', 'status'],
                'user_subscriptions_plan_status_index',
            );
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->index(
                ['subject_id', 'created_at'],
                'questions_subject_created_at_index',
            );
            $table->index(
                ['created_by', 'created_at'],
                'questions_created_by_created_at_index',
            );
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->index(
                ['user_id', 'created_at'],
                'quiz_attempts_user_created_at_index',
            );
            $table->index(
                ['subject_id', 'created_at'],
                'quiz_attempts_subject_created_at_index',
            );
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->index(['is_active', 'sort_order', 'id'], 'faqs_active_sort_id_index');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(
                ['is_active', 'sort_order', 'id'],
                'reviews_active_sort_id_index',
            );
            $table->index(['user_id', 'is_active'], 'reviews_user_active_index');
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->index(
                ['inviter_id', 'created_at'],
                'referrals_inviter_created_at_index',
            );
        });

        Schema::table('referral_offers', function (Blueprint $table) {
            $table->index(
                ['is_active', 'sort_order'],
                'referral_offers_active_sort_index',
            );
            $table->index(
                ['starts_at', 'ends_at'],
                'referral_offers_starts_ends_index',
            );
        });

        Schema::table('referral_redemptions', function (Blueprint $table) {
            $table->index(
                ['user_id', 'expires_at'],
                'referral_redemptions_user_expires_index',
            );
            $table->index(
                ['user_id', 'created_at'],
                'referral_redemptions_user_created_index',
            );
        });

        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->index(
                ['read_at', 'created_at'],
                'admin_notifications_read_created_index',
            );
            $table->index(
                ['type', 'created_at'],
                'admin_notifications_type_created_index',
            );
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->index(
                ['provider', 'status', 'processed_at'],
                'payment_transactions_provider_status_processed_index',
            );
            $table->index(
                ['user_id', 'created_at'],
                'payment_transactions_user_created_index',
            );
            $table->index(
                ['plan_id', 'created_at'],
                'payment_transactions_plan_created_index',
            );
        });

        Schema::table('plan_subject_accesses', function (Blueprint $table) {
            $table->index(
                ['subject_id', 'plan_id'],
                'plan_subject_accesses_subject_plan_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_subject_accesses', function (Blueprint $table) {
            $table->dropIndex('plan_subject_accesses_subject_plan_index');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex('payment_transactions_provider_status_processed_index');
            $table->dropIndex('payment_transactions_user_created_index');
            $table->dropIndex('payment_transactions_plan_created_index');
        });

        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->dropIndex('admin_notifications_read_created_index');
            $table->dropIndex('admin_notifications_type_created_index');
        });

        Schema::table('referral_redemptions', function (Blueprint $table) {
            $table->dropIndex('referral_redemptions_user_expires_index');
            $table->dropIndex('referral_redemptions_user_created_index');
        });

        Schema::table('referral_offers', function (Blueprint $table) {
            $table->dropIndex('referral_offers_active_sort_index');
            $table->dropIndex('referral_offers_starts_ends_index');
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropIndex('referrals_inviter_created_at_index');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_active_sort_id_index');
            $table->dropIndex('reviews_user_active_index');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex('faqs_active_sort_id_index');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('quiz_attempts_user_created_at_index');
            $table->dropIndex('quiz_attempts_subject_created_at_index');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_subject_created_at_index');
            $table->dropIndex('questions_created_by_created_at_index');
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropIndex('user_subscriptions_user_status_end_index');
            $table->dropIndex('user_subscriptions_status_end_index');
            $table->dropIndex('user_subscriptions_plan_status_index');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropIndex('plans_plan_group_index');
            $table->dropIndex('plans_is_default_index');
            $table->dropIndex('plans_sort_price_id_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_name_index');
            $table->dropIndex('users_referred_by_index');
        });
    }
};

