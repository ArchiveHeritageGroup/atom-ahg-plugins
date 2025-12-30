<?php

declare(strict_types=1);

/**
 * Phase 6: Workflow and Loan Management Tables Migration.
 *
 * Creates tables for:
 * - workflow_instance: Active workflow instances
 * - workflow_history: Workflow transition history
 * - loan: Loan records
 * - loan_object: Objects on loan
 * - loan_document: Loan-related documents
 * - loan_extension: Loan extension history
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class WorkflowLoanMigration
{
    public function up(): void
    {
        $this->createWorkflowInstanceTable();
        $this->createWorkflowHistoryTable();
        $this->createLoanTable();
        $this->createLoanObjectTable();
        $this->createLoanDocumentTable();
        $this->createLoanExtensionTable();

        echo "Workflow and loan tables created successfully.\n";
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('loan_extension');
        Capsule::schema()->dropIfExists('loan_document');
        Capsule::schema()->dropIfExists('loan_object');
        Capsule::schema()->dropIfExists('loan');
        Capsule::schema()->dropIfExists('workflow_history');
        Capsule::schema()->dropIfExists('workflow_instance');

        echo "Workflow and loan tables dropped.\n";
    }

    private function createWorkflowInstanceTable(): void
    {
        if (Capsule::schema()->hasTable('workflow_instance')) {
            echo "Table workflow_instance already exists.\n";

            return;
        }

        Capsule::schema()->create('workflow_instance', function (Blueprint $table) {
            $table->id();

            // Workflow identification
            $table->string('workflow_id', 50);  // e.g., 'loan_out', 'loan_in', 'object_entry'

            // Entity being tracked
            $table->string('entity_type', 50);  // e.g., 'loan', 'entry'
            $table->unsignedInteger('entity_id');

            // Current state
            $table->string('current_state', 50);
            $table->boolean('is_complete')->default(false);

            // Metadata (JSON)
            $table->json('metadata')->nullable();

            // Timestamps
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('completed_at')->nullable();

            // Indexes
            $table->index('workflow_id', 'idx_wi_workflow');
            $table->index(['entity_type', 'entity_id'], 'idx_wi_entity');
            $table->index('current_state', 'idx_wi_state');
            $table->index('is_complete', 'idx_wi_complete');
        });

        echo "Created table: workflow_instance\n";
    }

    private function createWorkflowHistoryTable(): void
    {
        if (Capsule::schema()->hasTable('workflow_history')) {
            echo "Table workflow_history already exists.\n";

            return;
        }

        Capsule::schema()->create('workflow_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workflow_instance_id');

            // Transition details
            $table->string('from_state', 50)->nullable();  // Null for initial creation
            $table->string('to_state', 50);
            $table->string('transition', 50);  // Transition name executed

            // Who and when
            $table->unsignedInteger('user_id');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('workflow_instance_id', 'idx_wh_instance');
            $table->index('created_at', 'idx_wh_created');

            $table->foreign('workflow_instance_id')
                ->references('id')
                ->on('workflow_instance')
                ->onDelete('cascade');
        });

        echo "Created table: workflow_history\n";
    }

    private function createLoanTable(): void
    {
        if (Capsule::schema()->hasTable('loan')) {
            echo "Table loan already exists.\n";

            return;
        }

        Capsule::schema()->create('loan', function (Blueprint $table) {
            $table->id();

            // Loan identification
            $table->string('loan_number', 50)->unique();
            $table->enum('loan_type', ['out', 'in']);
            $table->enum('purpose', [
                'exhibition', 'research', 'conservation', 'photography',
                'education', 'filming', 'long_term', 'other',
            ])->default('exhibition');

            // Description
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();

            // Partner institution
            $table->string('partner_institution', 255);
            $table->string('partner_contact_name', 255)->nullable();
            $table->string('partner_contact_email', 255)->nullable();
            $table->string('partner_contact_phone', 100)->nullable();
            $table->text('partner_address')->nullable();

            // Dates
            $table->date('request_date');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('return_date')->nullable();

            // Insurance
            $table->enum('insurance_type', [
                'borrower', 'lender', 'shared', 'government', 'self',
            ])->default('borrower');
            $table->decimal('insurance_value', 15, 2)->nullable();
            $table->string('insurance_currency', 10)->default('ZAR');
            $table->string('insurance_policy_number', 100)->nullable();
            $table->string('insurance_provider', 255)->nullable();

            // Fees
            $table->decimal('loan_fee', 12, 2)->nullable();
            $table->string('loan_fee_currency', 10)->default('ZAR');

            // Approval
            $table->unsignedInteger('internal_approver_id')->nullable();
            $table->date('approved_date')->nullable();

            // Notes
            $table->text('notes')->nullable();

            // Audit
            $table->unsignedInteger('created_by');
            $table->timestamps();

            // Indexes
            $table->index('loan_type', 'idx_loan_type');
            $table->index('partner_institution', 'idx_loan_partner');
            $table->index('start_date', 'idx_loan_start');
            $table->index('end_date', 'idx_loan_end');
            $table->index('return_date', 'idx_loan_return');
        });

        echo "Created table: loan\n";
    }

    private function createLoanObjectTable(): void
    {
        if (Capsule::schema()->hasTable('loan_object')) {
            echo "Table loan_object already exists.\n";

            return;
        }

        Capsule::schema()->create('loan_object', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');
            $table->unsignedInteger('information_object_id');

            // Cached object info (for external objects on loan-in)
            $table->string('object_title', 500)->nullable();
            $table->string('object_identifier', 100)->nullable();

            // Object-specific insurance
            $table->decimal('insurance_value', 15, 2)->nullable();

            // Condition tracking
            $table->unsignedBigInteger('condition_report_id')->nullable();

            // Requirements
            $table->text('special_requirements')->nullable();
            $table->text('display_requirements')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('loan_id', 'idx_lo_loan');
            $table->index('information_object_id', 'idx_lo_object');

            $table->foreign('loan_id')
                ->references('id')
                ->on('loan')
                ->onDelete('cascade');
        });

        echo "Created table: loan_object\n";
    }

    private function createLoanDocumentTable(): void
    {
        if (Capsule::schema()->hasTable('loan_document')) {
            echo "Table loan_document already exists.\n";

            return;
        }

        Capsule::schema()->create('loan_document', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');

            // Document details
            $table->enum('document_type', [
                'agreement', 'facilities_report', 'condition_report',
                'insurance_certificate', 'receipt', 'correspondence',
                'photograph', 'other',
            ]);
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('file_size')->nullable();

            // Description
            $table->text('description')->nullable();

            // Audit
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('loan_id', 'idx_ld_loan');
            $table->index('document_type', 'idx_ld_type');

            $table->foreign('loan_id')
                ->references('id')
                ->on('loan')
                ->onDelete('cascade');
        });

        echo "Created table: loan_document\n";
    }

    private function createLoanExtensionTable(): void
    {
        if (Capsule::schema()->hasTable('loan_extension')) {
            echo "Table loan_extension already exists.\n";

            return;
        }

        Capsule::schema()->create('loan_extension', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id');

            // Extension details
            $table->date('previous_end_date');
            $table->date('new_end_date');
            $table->text('reason')->nullable();

            // Approval
            $table->unsignedInteger('approved_by');
            $table->timestamp('created_at')->useCurrent();

            // Index
            $table->index('loan_id', 'idx_le_loan');

            $table->foreign('loan_id')
                ->references('id')
                ->on('loan')
                ->onDelete('cascade');
        });

        echo "Created table: loan_extension\n";
    }
}

// CLI execution
if ('cli' === php_sapi_name() && isset($argv[0]) && basename($argv[0]) === basename(__FILE__)) {
    require_once dirname(__DIR__, 4).'/atom-framework/vendor/autoload.php';

    $capsule = new Capsule();
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => getenv('ATOM_DB_HOST') ?: 'localhost',
        'database' => getenv('ATOM_DB_NAME') ?: 'archive',
        'username' => getenv('ATOM_DB_USER') ?: 'root',
        'password' => getenv('ATOM_DB_PASS') ?: 'Merlot@123',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    $migration = new WorkflowLoanMigration();

    $action = $argv[1] ?? 'up';

    if ('down' === $action) {
        $migration->down();
    } else {
        $migration->up();
    }
}
