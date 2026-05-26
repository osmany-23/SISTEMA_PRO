<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('customers')->upsert([
            [
                'id' => 1,
                'name' => 'CLIENTE ORDINARIO',
                'email' => 'bryam.autorepuestos@gmail.com',
                'phone' => '89454301',
                'cedula' => null,
                'dob' => null,
                'country' => 'Nicaragua',
                'city' => 'JALAPA',
                'address' => 'DE DONDE FUE PANADRIA ROCHA MEDIA CUADRA AL ESTE',
            ],
        ], ['id'], ['name', 'email', 'phone', 'cedula', 'dob', 'country', 'city', 'address']);

        DB::table('warehouses')->upsert([
            [
                'id' => 1,
                'name' => 'AUTO REPUESTOS BRYAN #1',
                'phone' => '89454301',
                'country' => 'Nicaragua',
                'city' => 'JALAPA',
                'email' => 'autorepuestosbryan#1@gmail.com',
                'zip_code' => '39200',
            ],
        ], ['id'], ['name', 'phone', 'country', 'city', 'email', 'zip_code']);

        DB::table('currencies')->upsert([
            ['id' => 1, 'name' => 'CORDOBAS', 'code' => 'NIO', 'symbol' => 'C$'],
            ['id' => 2, 'name' => 'DOLARES', 'code' => 'USD', 'symbol' => '$'],
            ['id' => 3, 'name' => 'India', 'code' => 'INR', 'symbol' => '₹'],
        ], ['id'], ['name', 'code', 'symbol']);

        foreach ($this->settings() as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $this->normalizeValue($value)]
            );
        }
    }

    /**
     * @return array<string, string|int|bool>
     */
    private function settings(): array
    {
        return [
            'show_version_on_footer' => true,
            'country' => 'Nicaragua',
            'state' => 'Nueva Segovia',
            'city' => 'JALAPA',
            'postcode' => '39200',
            'date_format' => 'd-m-y',
            'purchase_code' => 'PU',
            'purchase_return_code' => 'PR',
            'sale_code' => 'SA',
            'sale_return_code' => 'SR',
            'expense_code' => 'EX',
            'is_currency_right' => true,
            'show_logo_in_receipt' => true,
            'show_app_name_in_sidebar' => false,
            'require_initial_payment' => false,
            'show_note' => true,
            'show_phone' => true,
            'show_customer' => true,
            'show_address' => true,
            'show_email' => false,
            'show_warehouse' => true,
            'show_tax_discount_shipping' => true,
            'show_barcode_in_receipt' => true,
            'notes' => 'NO SE ACEPTAN CAMBIOS NI DEVOLUCIONES',
            'show_product_code' => false,
            'currency' => 1,
            'email' => 'bryam.system2023@gmail.com',
            'company_name' => 'AUTO REPUESTOS BRYAN',
            'phone' => '58637131',
            'developed' => 'OSMANY CASCO',
            'footer' => '2025 Developed by InventaPRO All rights reserved - v1.1.0',
            'default_language' => '1',
            'default_customer' => 1,
            'default_warehouse' => 1,
            'address' => 'DE DONDE FUE PANADERIA ROCHA 1C AL ESTE',
            'stripe_key' => 'pu_test_yBzA1qI1PcfRBAVn1vJG2VuS00HcyhQX9LASERTFDDS',
            'stripe_secret' => 'pu_test_yBzA1qI1PcfRBAVn1vJG2VuS00HcyhQX9LASERTFDDS',
            'sms_gateway' => 1,
            'twillo_sid' => 'asd',
            'twillo_token' => 'asd',
            'twillo_from' => 'asd',
            'smtp_host' => 'mailtrap.io',
            'smtp_port' => '2525',
            'smtp_username' => 'test',
            'smtp_password' => 'test',
            'smtp_Encryption' => 'tls',
            'logo' => 'http://192.168.1.49/uploads/settings/2962/ChatGPT-Image-21-nov-2025,-09_56_47-a.m.-Photoroom-Photoroom.png',
            'credit_alert_days' => 3,
            'business_trade_name' => 'Calidad y confianza para tu vehiculo',
            'business_ruc' => '',
            'business_country' => 'Nicaragua',
            'business_phone_secondary' => '',
            'business_whatsapp' => '89454301',
            'business_emails' => 'bryam.system2023@gmail.com',
            'business_website' => '',
            'business_facebook' => 'Auto Repuestos Bryan',
            'business_instagram' => '',
            'business_tiktok' => '',
            'business_linkedin' => '',
            'business_youtube' => '',
            'business_secondary_logo' => '',
            'business_favicon' => '',
            'business_primary_color' => '#c1121f',
            'business_secondary_color' => '#111827',
            'business_button_color' => '#c1121f',
            'business_pdf_color' => '#d90416',
            'business_header_color' => '#07111b',
            'business_table_color' => '#111827',
            'business_tax_regime' => '',
            'business_tax_information' => '',
            'business_legal_text' => 'AUTO REPUESTOS BRYAN',
            'business_terms' => 'Esta cotizacion tiene validez segun los dias indicados. Los precios incluyen IVA y estan sujetos a disponibilidad.',
            'business_warranty_policy' => 'La garantia aplica unicamente por defectos de fabrica.',
            'business_return_policy' => 'No se aceptan devoluciones en piezas electricas o con instalacion.',
            'quotation_validity_days' => 15,
            'quotation_validity_text' => 'Esta cotizacion es valida durante los dias indicados.',
            'quotation_auto_observations' => 'Para proceder con su pedido, por favor confirmar disponibilidad.',
            'quotation_thank_you_text' => 'Gracias por confiar en nosotros.',
            'quotation_footer_text' => 'Soluciones automotrices profesionales para su negocio.',
            'quotation_digital_signature' => '',
            'quotation_company_stamp' => '',
            'quotation_company_qr' => '',
            'quotation_show_product_image' => true,
            'quotation_benefit_1_title' => 'Repuestos originales',
            'quotation_benefit_1_subtitle' => 'Calidad garantizada',
            'quotation_benefit_1_image' => '',
            'quotation_benefit_2_title' => 'Mejores precios',
            'quotation_benefit_2_subtitle' => 'Competitivos',
            'quotation_benefit_2_image' => '',
            'quotation_benefit_3_title' => 'Asesoria profesional',
            'quotation_benefit_3_subtitle' => 'Te ayudamos a elegir',
            'quotation_benefit_3_image' => '',
            'quotation_benefit_4_title' => 'Entrega rapida',
            'quotation_benefit_4_subtitle' => 'En Jalapa y alrededores',
            'quotation_benefit_4_image' => '',
        ];
    }

    /**
     * @param  bool|int|string  $value
     */
    private function normalizeValue($value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
