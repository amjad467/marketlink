<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Direct B2B Conversation Thread between Supplier and Retailer
        Schema::create('b2b_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('retailer_company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('subject')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['supplier_company_id', 'retailer_company_id']);
        });

        // 2. Direct Messages exchanged in Negotiation
        Schema::create('b2b_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('b2b_conversations')->cascadeOnDelete();
            $table->foreignId('sender_company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('message_type', ['text', 'rfq', 'quote_proposal', 'counteroffer', 'po_notice', 'attachment', 'image', 'voice'])->default('text');
            $table->text('body')->nullable();
            $table->string('attachment_url')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // 3. Requests for Quotation (RFQs) created by Retailers
        Schema::create('quotation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('rfq_reference')->unique();
            $table->foreignId('retailer_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('b2b_conversations')->nullOnDelete();
            $table->enum('status', ['open', 'quoted', 'expired', 'closed'])->default('open');
            $table->date('requested_delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained('quotation_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('requested_quantity');
            $table->decimal('target_unit_price', 12, 2)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        // 4. Commercial Quotation Proposals & Counteroffers
        Schema::create('quotation_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->nullable()->constrained('quotation_requests')->nullOnDelete();
            $table->foreignId('conversation_id')->constrained('b2b_conversations')->cascadeOnDelete();
            $table->string('proposal_reference')->unique();
            $table->foreignId('proposer_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('receiver_company_id')->constrained('companies')->cascadeOnDelete();
            $table->integer('version')->default(1);
            $table->enum('status', ['draft', 'pending_response', 'countered', 'accepted', 'rejected', 'expired'])->default('pending_response');
            $table->string('currency', 10)->default('USD');
            $table->decimal('subtotal_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2);
            $table->string('payment_terms');
            $table->string('delivery_terms');
            $table->timestamp('valid_until');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quotation_proposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('quotation_proposals')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_title');
            $table->string('sku');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();
        });

        // 5. Binding Purchase Orders directly between Retailer and Supplier
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('quotation_proposal_id')->nullable()->constrained('quotation_proposals')->nullOnDelete();
            $table->foreignId('retailer_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('status', [
                'issued',
                'accepted',
                'processing',
                'dispatched',
                'delivered',
                'completed',
                'cancelled'
            ])->default('issued');
            $table->string('currency', 10)->default('USD');
            $table->decimal('subtotal_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2);
            $table->string('payment_terms');
            $table->text('delivery_address');
            $table->date('estimated_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->text('supplier_notes')->nullable();
            $table->text('retailer_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_title');
            $table->string('sku');
            $table->integer('quantity_ordered');
            $table->integer('quantity_delivered')->default(0);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();
        });

        // 6. Direct Supplier Invoices
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('supplier_company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('retailer_company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 10)->default('USD');
            $table->enum('settlement_status', ['unpaid', 'partially_paid', 'paid', 'cancelled'])->default('unpaid');
            $table->string('payment_receipt_ref')->nullable();
            $table->string('invoice_pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('quotation_proposal_items');
        Schema::dropIfExists('quotation_proposals');
        Schema::dropIfExists('quotation_request_items');
        Schema::dropIfExists('quotation_requests');
        Schema::dropIfExists('b2b_messages');
        Schema::dropIfExists('b2b_conversations');
    }
};
