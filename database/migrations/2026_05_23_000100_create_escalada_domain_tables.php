<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('group_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('membro');
            $table->string('status')->default('aprovado');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['group_id', 'user_id']);
        });

        Schema::create('invite_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('role')->default('membro');
            $table->boolean('requires_approval')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('street')->nullable();
            $table->string('number')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('complement')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('event_functions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_initially_active')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['group_id', 'name']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('name');
            $table->date('event_date');
            $table->time('event_time');
            $table->text('notes')->nullable();
            $table->string('status')->default('agendado');
            $table->string('liturgical_color')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('event_function_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_function_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('slot_order')->default(1);
            $table->string('status')->default('aberta');
            $table->foreignId('approved_candidate_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['event_id', 'event_function_id', 'slot_order'], 'efs_event_func_slot_unique');
        });

        Schema::create('event_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_function_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamp('assigned_at');
            $table->timestamps();
            $table->softDeletes();
            $table->unique('event_function_slot_id');
        });

        Schema::create('event_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_function_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pendente');
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['event_function_slot_id', 'user_id']);
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pendente');
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });

        Schema::create('substitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('replaced_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('replacement_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('source')->default('manual');
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('event_environment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('environment');
            $table->string('photo_path');
            $table->text('observation')->nullable();
            $table->foreignId('submitted_by')->constrained('users');
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['event_id', 'environment']);
        });

        Schema::create('ghost_account_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ghost_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('real_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pendente');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ghost_account_claims');
        Schema::dropIfExists('event_environment_submissions');
        Schema::dropIfExists('substitutions');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('event_candidates');
        Schema::dropIfExists('event_assignments');
        Schema::dropIfExists('event_function_slots');
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_functions');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('invite_links');
        Schema::dropIfExists('group_memberships');
        Schema::dropIfExists('groups');
    }
};
