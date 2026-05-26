<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Resources\SettingResource;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\State;
use App\Models\Warehouse;
use App\Repositories\SettingRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Class SettingAPIController
 */
class SettingAPIController extends AppBaseController
{
    /** @var SettingRepository */
    private $settingRepository;

    public function __construct(SettingRepository $productRepository)
    {
        $this->settingRepository = $productRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $settings['logo'] = getLogoUrl();
        $settings['warehouse_name'] = Warehouse::whereId($settings['default_warehouse'])->first()->name ?? '';
        $settings['customer_name'] = Customer::whereId($settings['default_customer'])->first()->name ?? '';
        $settings['currency_symbol'] = Currency::whereId($settings['currency'])->first()->symbol ?? '';
        $settings['countries'] = Country::all();

        return $this->sendResponse(new SettingResource(['type' => 'settings', 'attributes' => $settings]),
            'Setting data retrieved successfully.');
    }

    public function update(Request $request): JsonResponse
    {
        $input = $request->all();
        $settings = $this->settingRepository->updateSettings($input);

        return $this->sendResponse(new SettingResource(['type' => 'settings', 'attributes' => $settings]),
            'Setting data updated successfully');
    }

    public function clearCache(): JsonResponse
    {
        Artisan::call('cache:clear');

        return $this->sendSuccess(__('messages.success.cache_clear_successfully'));
    }

    public function getFrontSettingsValue(): JsonResponse
    {
        $keyName = [
            'currency', 'email', 'company_name', 'phone', 'developed', 'footer', 'default_language', 'default_customer',
            'default_warehouse', 'address', 'show_app_name_in_sidebar', 'require_initial_payment',
            'city', 'country', 'business_trade_name', 'business_ruc', 'business_country',
            'business_phone_secondary', 'business_whatsapp', 'business_emails', 'business_website',
            'business_facebook', 'business_instagram', 'business_tiktok', 'business_linkedin',
            'business_youtube', 'business_secondary_logo', 'business_favicon', 'business_primary_color',
            'business_secondary_color', 'business_button_color', 'business_pdf_color',
            'business_header_color', 'business_table_color', 'business_tax_regime',
            'business_tax_information', 'business_legal_text', 'business_terms',
            'business_warranty_policy', 'business_return_policy', 'quotation_validity_days',
            'quotation_validity_text', 'quotation_auto_observations', 'quotation_thank_you_text',
            'quotation_footer_text', 'quotation_digital_signature', 'quotation_company_stamp',
            'quotation_company_qr', 'quotation_benefit_1_title', 'quotation_benefit_1_subtitle',
            'quotation_benefit_1_image', 'quotation_benefit_2_title', 'quotation_benefit_2_subtitle',
            'quotation_benefit_2_image', 'quotation_benefit_3_title', 'quotation_benefit_3_subtitle',
            'quotation_benefit_3_image', 'quotation_benefit_4_title', 'quotation_benefit_4_subtitle',
            'quotation_benefit_4_image', 'quotation_show_product_image',
        ];
        $settings = Setting::whereIn('key', $keyName)->pluck('value', 'key')->toArray();
        $settings['logo'] = getLogoUrl();
        $settings['warehouse_name'] = Warehouse::whereId($settings['default_warehouse'])->first()->name ?? '';
        $settings['customer_name'] = Customer::whereId($settings['default_customer'])->first()->name ?? '';
        $settings['currency_symbol'] = Currency::whereId($settings['currency'])->first()->symbol ?? '';

        return $this->sendResponse(new SettingResource(['type' => 'settings', 'value' => $settings]),
            'Setting value retrieved successfully.');
    }

    public function getStates($countryId): JsonResponse
    {
        $states = State::whereCountryId($countryId)->pluck('name');

        return $this->sendResponse(new SettingResource(['type' => 'states', 'value' => $states]),
            'States retrieved successfully.');
    }

    public function getMailSettings()
    {
        $envData = $this->settingRepository->getEnvData();

        return $this->sendResponse($envData, 'Mail Credential Retrieved Successfully');
    }

    public function updateMailSettings(Request $request): JsonResponse
    {
        $request->validate([
            'mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_from_address', 'mail_encryption',
        ]);
        $this->settingRepository->updateMailEnvSetting($request->all());

        Artisan::call('optimize:clear');
        Artisan::call('config:cache');

        return $this->sendSuccess('Mail Settings Save Successfully');
    }

    public function updateReceiptSetting(Request $request)
    {
        $settings = $this->settingRepository->updateReceiptSetting($request->all());

        return $this->sendResponse(new SettingResource(['type' => 'settings', 'attributes' => $settings]),
            'Setting data updated successfully');
    }
}
