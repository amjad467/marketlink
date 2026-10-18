<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\PlatformAdmin;
use App\Models\Company;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Platform Operator Superadmin
        $admin = PlatformAdmin::create([
            'name' => 'MarketLink Governance Admin',
            'email' => 'admin@marketlink.io',
            'password' => Hash::make('Operator@2026!'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        \App\Models\User::firstOrCreate(
            ['email' => 'admin@marketlink.krd'],
            [
                'name' => 'Platform Operator Admin (بەڕێوەبەری پلاتفۆرم)',
                'password' => Hash::make(env('INITIAL_ADMIN_PASSWORD', 'password')),
                'role' => 'platform_admin',
                'is_active' => true,
            ]
        );

        // 2. Global Taxonomies
        $catDairy = ProductCategory::create([
            'name_en' => 'Dairy & Refrigerated',
            'name_ar' => 'منتجات الألبان والمبردة',
            'name_ku' => 'بەرهەمە شیرییەکان و ساردکەرەوە',
            'slug' => 'dairy-refrigerated',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $catPantry = ProductCategory::create([
            'name_en' => 'Pantry & Staples',
            'name_ar' => 'المواد الغذائية الأساسية',
            'name_ku' => 'خۆراکی بنەڕەتی و پێداویستی کۆگا',
            'slug' => 'pantry-staples',
            'is_active' => true,
            'display_order' => 2,
        ]);

        $catBeverages = ProductCategory::create([
            'name_en' => 'Beverages & Syrups',
            'name_ar' => 'المشروبات والمركزات',
            'name_ku' => 'خواردنەوەکان و شەربەت',
            'slug' => 'beverages-syrups',
            'is_active' => true,
            'display_order' => 3,
        ]);

        // 3. Verified Supplier: Apex Wholesale Ltd
        $supplier = Company::create([
            'name' => 'Apex Wholesale Ltd',
            'trade_license_number' => 'TL-APEX-7712',
            'tax_number' => 'TAX-AE-9921',
            'business_type' => 'supplier',
            'verification_status' => 'verified',
            'account_status' => 'active',
            'primary_email' => 'sales@apexwholesale.com',
            'primary_phone' => '+971-4-889-1000',
            'website' => 'https://apexwholesale.com',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address_line' => 'Al Quoz Industrial Area 3, Warehouse 14',
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);

        DB::table('supplier_profiles')->insert([
            'company_id' => $supplier->id,
            'minimum_order_total' => 250.00,
            'default_payment_terms' => 'Net 30 Days',
            'lead_time_days' => 2,
            'currency' => 'USD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Models\User::firstOrCreate(
            ['email' => 'supplier_1@marketlink.krd'],
            [
                'company_id' => $supplier->id,
                'name' => 'Apex Wholesale Ltd (دابینکەر)',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'is_active' => true,
            ]
        );

        \App\Models\User::firstOrCreate(
            ['email' => 'driver@marketlink.krd'],
            [
                'company_id' => $supplier->id,
                'name' => 'شۆفێری دابەشکردن و گەیاندن',
                'password' => Hash::make('password'),
                'role' => 'driver',
                'is_active' => true,
            ]
        );

        // 4. Verified Retailer: Metro Supermarkets
        $retailer = Company::create([
            'name' => 'Metro Supermarkets Chain',
            'trade_license_number' => 'TL-METRO-3091',
            'tax_number' => 'TAX-AE-5510',
            'business_type' => 'retailer',
            'verification_status' => 'verified',
            'account_status' => 'active',
            'primary_email' => 'procurement@metrosupermarkets.com',
            'primary_phone' => '+971-4-332-9090',
            'website' => 'https://metrosupermarkets.com',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address_line' => 'Business Bay, Tower B, Level 18',
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ]);

        DB::table('retailer_profiles')->insert([
            'company_id' => $retailer->id,
            'store_count' => 14,
            'default_delivery_address' => 'Central Distribution Hub, Dubai Investments Park',
            'preferred_payment_method' => 'Bank Transfer / Net 30',
            'receiving_hours' => '07:00 AM - 03:00 PM',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Models\User::firstOrCreate(
            ['email' => 'retailer_2@marketlink.krd'],
            [
                'company_id' => $retailer->id,
                'name' => 'Metro Supermarkets Chain (مارکێت)',
                'password' => Hash::make('password'),
                'role' => 'owner',
                'is_active' => true,
            ]
        );

        // 5. Pending KYB Supplier
        $pendingSupplier = Company::create([
            'name' => 'Nordic Dairy Distributors LLC',
            'trade_license_number' => 'TL-NORDIC-8942',
            'tax_number' => 'TAX-9923812',
            'business_type' => 'supplier',
            'verification_status' => 'pending',
            'account_status' => 'active',
            'primary_email' => 'contact@nordicdairy.com',
            'primary_phone' => '+971-4-551-0199',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address_line' => 'Dubai South Free Zone, B4',
        ]);

        DB::table('business_verifications')->insert([
            'company_id' => $pendingSupplier->id,
            'document_type' => 'trade_license',
            'file_path' => 'documents/kyb/nordic_license_2026.pdf',
            'review_status' => 'submitted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6. Products for Apex Wholesale
        $p1 = Product::create([
            'supplier_company_id' => $supplier->id,
            'category_id' => $catDairy->id,
            'sku' => 'APX-BUT-01',
            'barcode' => '6291004928101',
            'title' => 'Artisan Salted Butter (250g Blocks)',
            'description' => 'Grade-A European churned butter with sea salt crystals.',
            'unit_of_measure' => 'piece',
            'base_wholesale_price' => 2.80,
            'currency' => 'USD',
            'minimum_order_quantity' => 50,
            'current_stock' => 4200,
            'is_available' => true,
            'is_active' => true,
        ]);

        DB::table('product_volume_tiers')->insert([
            ['product_id' => $p1->id, 'min_quantity' => 50, 'unit_price' => 2.70, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $p1->id, 'min_quantity' => 200, 'unit_price' => 2.45, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $p1->id, 'min_quantity' => 500, 'unit_price' => 2.20, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $p2 = Product::create([
            'supplier_company_id' => $supplier->id,
            'category_id' => $catPantry->id,
            'sku' => 'APX-OIL-EV5',
            'barcode' => '6291004928205',
            'title' => 'Extra Virgin Olive Oil (5L Foodservice Tin)',
            'description' => 'Cold-pressed extra virgin olive oil for professional kitchens and retail.',
            'unit_of_measure' => 'tin',
            'base_wholesale_price' => 38.50,
            'currency' => 'USD',
            'minimum_order_quantity' => 10,
            'current_stock' => 650,
            'is_available' => true,
            'is_active' => true,
        ]);

        DB::table('product_volume_tiers')->insert([
            ['product_id' => $p2->id, 'min_quantity' => 10, 'unit_price' => 37.00, 'created_at' => now(), 'updated_at' => now()],
            ['product_id' => $p2->id, 'min_quantity' => 50, 'unit_price' => 34.50, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 7. Commercial Direct PO between Metro and Apex
        $po = PurchaseOrder::create([
            'po_number' => 'PO-2026-9041',
            'retailer_company_id' => $retailer->id,
            'supplier_company_id' => $supplier->id,
            'status' => 'dispatched',
            'currency' => 'USD',
            'subtotal_amount' => 1725.00,
            'discount_amount' => 0.00,
            'tax_amount' => 0.00,
            'total_amount' => 1725.00,
            'payment_terms' => 'Net 15 Days Direct Settlement',
            'delivery_address' => 'Metro Supermarkets Central Distribution Hub, DIP, Dubai',
            'estimated_delivery_date' => now()->addDay(),
            'supplier_notes' => 'Dispatched via Apex Logistics Fleet with temperature loggers.',
            'retailer_notes' => 'Dock bay 4 receiving.',
        ]);

        DB::table('purchase_order_items')->insert([
            'purchase_order_id' => $po->id,
            'product_id' => $p2->id,
            'product_title' => $p2->title,
            'sku' => $p2->sku,
            'quantity_ordered' => 50,
            'quantity_delivered' => 50,
            'unit_price' => 34.50,
            'total_price' => 1725.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 8. B2B Chat & Negotiation Thread between Metro and Apex
        $conv = \App\Models\B2bConversation::create([
            'supplier_company_id' => $supplier->id,
            'retailer_company_id' => $retailer->id,
            'subject' => 'داواکاری نرخی کۆمەڵ و پێداویستی مارکێت',
            'last_message_at' => now(),
        ]);

        \App\Models\B2bMessage::create([
            'conversation_id' => $conv->id,
            'sender_company_id' => $retailer->id,
            'message_type' => 'text',
            'body' => 'سڵاو لە دابینکەری بەڕێز، پێویستیمان بە داواکارییەکی نوێی کەرەی خوێدار (250g) هەیە بە بڕی ٥٠٠ کارتۆن، تکایە ئایا داشکاندنی زیاتر هەیە و کاتی گەیاندن چەند ڕۆژە؟',
            'created_at' => now()->subHours(2),
        ]);

        \App\Models\B2bMessage::create([
            'conversation_id' => $conv->id,
            'sender_company_id' => $supplier->id,
            'message_type' => 'text',
            'body' => 'بەخێربێن مارکێتی میترۆ! بەڵێ بەپێی پلەی داشکاندنی بڕ نرخ بۆ ٥٠٠ دانە دەبێتە $2.20 بۆ هەر یەکەیەک بە شێوازی پارەدانی ٣٠ ڕۆژ (Net 30) و کاتی گەیاندن ٢ ڕۆژە. دەتوانن لە بەشی کڕین ڕاستەوخۆ داواکاری بنێرن.',
            'created_at' => now()->subHour(),
        ]);
    }
}
