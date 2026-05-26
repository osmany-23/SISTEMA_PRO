<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Http\Resources\QuotationCollection;
use App\Http\Resources\QuotationResource;
use App\Models\Customer;
use App\Models\MainProduct;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Repositories\QuotationRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class QuotationAPIController extends AppBaseController
{
    /** @var quotationRepository */
    private $quotationRepository;

    public function __construct(QuotationRepository $quotationRepository)
    {
        $this->quotationRepository = $quotationRepository;
    }

    public function index(Request $request)
    {
        $perPage = getPageSize($request);
        $search = $request->filter['search'] ?? '';
        $customer = (Customer::where('name', 'LIKE', "%$search%")->get()->count() != 0);
        $warehouse = (Warehouse::where('name', 'LIKE', "%$search%")->get()->count() != 0);

        $quotations = $this->quotationRepository;
        if ($customer || $warehouse) {
            $quotations->whereHas('customer', function (Builder $q) use ($search, $customer) {
                if ($customer) {
                    $q->where('name', 'LIKE', "%$search%");
                }
            })->whereHas('warehouse', function (Builder $q) use ($search, $warehouse) {
                if ($warehouse) {
                    $q->where('name', 'LIKE', "%$search%");
                }
            });
        }

        if ($request->get('start_date') && $request->get('end_date')) {
            $quotations->whereBetween('date', [$request->get('start_date'), $request->get('end_date')]);
        }

        if ($request->get('warehouse_id')) {
            $quotations->where('warehouse_id', $request->get('warehouse_id'));
        }

        if ($request->get('customer_id')) {
            $quotations->where('customer_id', $request->get('customer_id'));
        }

        if ($request->get('status') && $request->get('status') != 'null') {
            $quotations->Where('status', $request->get('status'));
        }

        $quotations = $quotations->paginate($perPage);

        QuotationResource::usingWithCollection();

        return new QuotationCollection($quotations);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    public function store(CreateQuotationRequest $request): QuotationResource
    {
        $input = $request->all();
        $quotation = $this->quotationRepository->storeQuotation($input);

        return new QuotationResource($quotation);
    }

    public function show($id): QuotationResource
    {
        $quotationRelations = [
            'quotationItems.product.brand',
            'quotationItems.product.media',
            'quotationItems.product.mainProduct.media',
            'warehouse',
            'customer',
        ];
        if (Schema::hasTable('product_batches') && Schema::hasColumn('quotation_items', 'product_batch_id')) {
            $quotationRelations[] = 'quotationItems.productBatch';
        }
        $quotation = Quotation::query()->with($quotationRelations)->findOrFail($id);
        $this->attachQuotationProductImages($quotation);

        return new QuotationResource($quotation);
    }

    public function quotationInfo(Quotation $quotation): JsonResponse
    {
        $quotationRelations = ['quotationItems.product.media', 'quotationItems.product.brand', 'quotationItems.product.mainProduct.media', 'warehouse', 'customer'];
        if (Schema::hasTable('product_batches') && Schema::hasColumn('quotation_items', 'product_batch_id')) {
            $quotationRelations[] = 'quotationItems.productBatch';
        }
        $quotation = $quotation->load($quotationRelations);
        $this->attachQuotationProductImages($quotation);
        $keyName = [
            'email', 'company_name', 'phone', 'address', 'city', 'country',
            'business_trade_name', 'business_ruc', 'business_country', 'business_phone_secondary',
            'business_whatsapp', 'business_emails', 'business_website', 'business_facebook',
            'business_instagram', 'business_tiktok', 'business_linkedin', 'business_youtube',
            'business_secondary_logo', 'business_favicon', 'business_primary_color',
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
        $quotation['company_info'] = Setting::whereIn('key', $keyName)->pluck('value', 'key')->toArray();
        $quotation['company_info']['logo'] = getLogoUrl();
        $quotation['seller_name'] = $this->sellerName();

        return $this->sendResponse($quotation, 'Quotation information retrieved successfully');
    }

    public function edit(Quotation $quotation): QuotationResource
    {
        $quotationRelations = ['quotationItems.product.stocks', 'warehouse'];
        if (Schema::hasTable('product_batches') && Schema::hasColumn('quotation_items', 'product_batch_id')) {
            $quotationRelations[] = 'quotationItems.productBatch';
        }
        $quotation = $quotation->load($quotationRelations);

        return new QuotationResource($quotation);
    }

    public function update(UpdateQuotationRequest $request, $id): QuotationResource
    {
        $input = $request->all();
        $quotation = $this->quotationRepository->updateQuotation($input, $id);

        return new QuotationResource($quotation);
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $this->quotationRepository->delete($quotation->id);

        return $this->sendSuccess('Quotation Deleted successfully');
    }

    public function pdfDownload(Quotation $quotation): JsonResponse
    {
        ini_set('memory_limit','-1');
        $quotation = $quotation->load('customer', 'warehouse', 'quotationItems.product.media', 'quotationItems.product.brand', 'quotationItems.product.mainProduct.media');
        $this->attachQuotationProductImages($quotation);
        $data = [];
        if (Storage::exists('pdf/Quotation-'.$quotation->reference_code.'.pdf')) {
            Storage::delete('pdf/Quotation-'.$quotation->reference_code.'.pdf');
        }
        $companyLogo = getLogoUrl();
        $companyLogo = (string) \Image::make($companyLogo)->encode('data-url');
        $defaultProductImage = (string) \Image::make(public_path('images/no-image.png'))->encode('data-url');

        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $settings['logo'] = getLogoUrl();

        $sellerName = $this->sellerName();

        $pdf = PDF::loadView('pdf.quotation-pdf', compact('quotation', 'companyLogo', 'settings', 'sellerName', 'defaultProductImage'))
            ->setPaper('a4', 'landscape');
        Storage::disk(config('app.media_disc'))->put('pdf/Quotation-'.$quotation->reference_code.'.pdf',
            $pdf->output());
        $data['quotation_pdf_url'] = Storage::url('pdf/Quotation-'.$quotation->reference_code.'.pdf');

        return $this->sendResponse($data, 'Quotation pdf retrieved Successfully');
    }

    private function sellerName(): string
    {
        $user = auth()->user();

        return trim((string) ($user->name ?? ''))
            ?: trim((string) (($user->first_name ?? '').' '.($user->last_name ?? '')))
            ?: (string) ($user->email ?? '');
    }

    private function attachQuotationProductImages(Quotation $quotation): void
    {
        $fallback = asset('images/no-image.png');

        $quotation->quotationItems->each(function ($item) use ($fallback) {
            if (! $item->product) {
                return;
            }

            $item->product->setAttribute('quotation_image_url', $this->productImageUrl($item->product, $fallback));
        });
    }

    private function productImageUrl(Product $product, string $fallback): string
    {
        $productMedia = $product->getMedia(Product::PATH)->first();
        if ($productMedia) {
            return str_replace('\\', '/', $productMedia->getFullUrl());
        }

        $mainProduct = $product->mainProduct;
        if ($mainProduct) {
            $mainMedia = $mainProduct->getMedia(MainProduct::PATH)->first();
            if ($mainMedia) {
                return str_replace('\\', '/', $mainMedia->getFullUrl());
            }
        }

        return $fallback;
    }
}
