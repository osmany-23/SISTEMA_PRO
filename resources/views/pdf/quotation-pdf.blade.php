<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
    <title>{{ __('messages.quotation_pdf') }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon.ico') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/font.css') }}">
    @php
        $setting = fn ($key, $fallback = '') => $settings[$key] ?? $fallback;
        $primary = $setting('business_pdf_color', $setting('business_primary_color', '#c1121f'));
        $header = $setting('business_header_color', '#07111b');
        $table = $setting('business_table_color', '#111827');
        $quotationMeta = $quotationMeta ?? $quotation->quotationPresentationData($settings);
        $validityDays = (int) ($quotationMeta['validity_days'] ?? \App\Models\Quotation::configuredValidityDays($settings));
        $showProductImage = $setting('quotation_show_product_image', '1') !== '0';
        $createdAt = \Carbon\Carbon::parse($quotationMeta['issued_at'] ?? ($quotation->created_at ?? $quotation->date));
        $expiresAt = \Carbon\Carbon::parse($quotationMeta['expires_at'] ?? $createdAt->copy()->addDays($validityDays));
        $subtotal = (float) ($quotationMeta['subtotal'] ?? $quotation->quotationSubtotal());
        $statusLabel = $quotationMeta['status_label'] ?? $quotation->quotationStatusLabel();
        $statusClass = ($quotationMeta['status_class'] ?? $quotation->quotationStatusClass()) === 'sent' ? 'approved' : 'pending';
        $socials = collect([
            'Facebook' => $setting('business_facebook'),
            'Instagram' => $setting('business_instagram'),
            'TikTok' => $setting('business_tiktok'),
            'LinkedIn' => $setting('business_linkedin'),
            'YouTube' => $setting('business_youtube'),
        ])->filter();
        $socialText = $socials->map(fn ($value, $name) => $name.': '.$value)->implode(' | ') ?: $setting('business_website');
        $firstImage = function ($value) use (&$firstImage) {
            if (empty($value)) {
                return null;
            }
            if (is_string($value)) {
                return $value;
            }
            if (is_array($value)) {
                if (! empty($value['imageUrls'][0])) {
                    return $value['imageUrls'][0];
                }
                foreach ($value as $item) {
                    $image = $firstImage($item);
                    if ($image) {
                        return $image;
                    }
                }
            }
            if (is_object($value)) {
                if (! empty($value->original_url)) {
                    return $value->original_url;
                }
                if (! empty($value->url)) {
                    return $value->url;
                }
            }

            return null;
        };
        $benefits = collect([
            ['title' => $setting('quotation_benefit_1_title', 'Repuestos originales'), 'subtitle' => $setting('quotation_benefit_1_subtitle', 'Calidad garantizada'), 'image' => $setting('quotation_benefit_1_image')],
            ['title' => $setting('quotation_benefit_2_title', 'Mejores precios'), 'subtitle' => $setting('quotation_benefit_2_subtitle', 'Competitivos'), 'image' => $setting('quotation_benefit_2_image')],
            ['title' => $setting('quotation_benefit_3_title', 'Asesoria profesional'), 'subtitle' => $setting('quotation_benefit_3_subtitle', 'Te ayudamos a elegir'), 'image' => $setting('quotation_benefit_3_image')],
            ['title' => $setting('quotation_benefit_4_title', 'Entrega rapida'), 'subtitle' => $setting('quotation_benefit_4_subtitle', 'En Jalapa y alrededores'), 'image' => $setting('quotation_benefit_4_image')],
        ]);
        $qr = $setting('quotation_company_qr');
    @endphp
    <style>
        * { box-sizing: border-box; font-family: "Arial-unicode-ms", DejaVu Sans, sans-serif; }
        @page { margin: 7mm 8mm; size: A4 landscape; }
        body { color: #111827; font-size: 9.4px; margin: 0; }
        .page { background: #f6f7f9; padding: 6px; }
        .hero { background: {{ $header }}; border-radius: 9px; color: #fff; overflow: hidden; width: 100%; }
        .hero td { vertical-align: middle; }
        .brand { padding: 10px 14px; width: 34%; }
        .logo { height: 86px; object-fit: contain; width: 160px; }
        .brand-name { color: #fff; font-size: 20px; font-weight: 900; line-height: 1; margin-top: 3px; text-transform: uppercase; }
        .tagline { color: #e5e7eb; font-size: 8.5px; font-weight: 700; text-transform: uppercase; }
        .center { border-left: 1px solid rgba(255,255,255,.32); padding: 10px 14px; width: 33%; }
        .contact-line { color: #f9fafb; font-size: 10px; line-height: 1.35; margin-bottom: 5px; width: 100%; }
        .contact-icon { background: {{ $primary }}; border-radius: 99px; display: inline-block; height: 7px; margin: 4px 9px 0 0; vertical-align: top; width: 7px; }
        .contact-label { display: inline-block; font-weight: 800; width: 68px; }
        .contact-value { display: inline-block; max-width: 205px; vertical-align: top; }
        .qr { text-align: center; width: 11%; }
        .qr img { background: #fff; border-radius: 7px; height: 72px; object-fit: contain; padding: 5px; width: 72px; }
        .qr div { background: {{ $primary }}; border-radius: 5px; color: #fff; font-size: 7.5px; font-weight: 900; margin: 5px auto 0; padding: 4px; text-transform: uppercase; width: 78px; }
        .quote-side { background: {{ $primary }}; color: #fff; padding: 11px 14px; text-align: center; width: 22%; }
        .quote-side .label { font-size: 9px; font-weight: 800; text-transform: uppercase; }
        .quote-side .number { color: #fff; font-size: 27px; font-weight: 900; line-height: 1.05; margin: 4px 0 6px; }
        .status { border-radius: 999px; display: inline-block; font-size: 11px; font-weight: 900; min-width: 110px; padding: 5px 10px; text-transform: uppercase; }
        .approved { background: #e4f8ea; color: #0f8a43; }
        .pending { background: #fff4cc; color: #946200; }
        .rejected { background: #ffe1e1; color: #aa1e1e; }
        .date-line { color: #fff; font-size: 10px; font-weight: 700; margin-top: 7px; text-align: left; }
        .strip { background: #fff; border: 1px solid #dfe4ea; border-radius: 7px; margin-top: 6px; padding: 7px 8px; width: 100%; }
        .strip td { border-left: 1px solid #e5e7eb; color: #374151; padding: 0 10px; vertical-align: top; }
        .strip td:first-child { border-left: 0; }
        .strip strong { color: #111827; display: block; font-size: 8.8px; }
        .cards { margin-top: 6px; width: 100%; }
        .card { background: #fff; border: 1px solid #dfe4ea; border-radius: 7px; padding: 8px 10px; vertical-align: top; width: 49%; }
        .card-title { color: #111827; font-size: 11px; font-weight: 900; margin-bottom: 7px; text-transform: uppercase; }
        .metric td { border-left: 1px solid #edf0f4; padding: 2px 11px; width: 25%; }
        .metric td:first-child { border-left: 0; padding-left: 0; }
        .metric strong { color: #111827; display: block; font-size: 8.8px; }
        .metric span { color: #374151; display: block; font-size: 9.3px; margin-top: 2px; }
        .items { border-collapse: collapse; margin-top: 6px; overflow: hidden; width: 100%; }
        .items th { background: {{ $table }}; color: #fff; font-size: 8px; font-weight: 900; padding: 5px 4px; text-transform: uppercase; vertical-align: middle; }
        .items td { background: #fff; border-bottom: 1px solid #edf0f4; color: #374151; font-size: 8.8px; padding: 4px 4px; text-align: center; vertical-align: middle; }
        .items td.name { text-align: left; width: 22%; }
        .code { color: {{ $primary }}; font-weight: 800; text-decoration: underline; }
        .product-img { border: 0; border-radius: 0; height: 40px; object-fit: contain; width: 64px; }
        .bottom { margin-top: 6px; width: 100%; }
        .notes { background: #fff; border: 1px solid #dfe4ea; border-radius: 7px; color: #374151; line-height: 1.25; padding: 8px 10px; vertical-align: top; width: 49%; }
        .notes p { margin: 3px 0; }
        .thanks { border-left: 4px solid {{ $primary }}; margin-top: 7px; padding-left: 10px; }
        .total-cell { vertical-align: top; width: 49%; }
        .totals { background: #fff; border: 1px solid #dfe4ea; border-radius: 7px; border-collapse: collapse; overflow: hidden; vertical-align: top; width: 100%; }
        .totals td { border-bottom: 1px solid #edf0f4; padding: 5px 12px; }
        .totals .label { color: #111827; font-weight: 800; }
        .totals .amount { font-weight: 900; text-align: right; }
        .grand td { background: {{ $primary }}; color: #fff; font-size: 15px; font-weight: 900; padding: 8px 12px; text-transform: uppercase; }
        .footer { margin-top: 6px; width: 100%; }
        .benefits { background: #fff; border-radius: 7px; padding: 7px; width: 40%; }
        .benefits td { color: #111827; font-size: 7.6px; font-weight: 900; text-align: center; text-transform: uppercase; vertical-align: top; width: 25%; }
        .benefit-img { display: block; height: 28px; margin: 0 auto 4px; object-fit: contain; width: 36px; }
        .benefits span { color: #4b5563; display: block; font-size: 7.3px; font-weight: 400; margin-top: 2px; text-transform: none; }
        .marks { text-align: center; width: 27%; }
        .mark-img { height: 43px; margin: 0 3px; object-fit: contain; width: 62px; }
        .terms { background: #fff; border-radius: 7px; color: #374151; line-height: 1.25; padding: 8px 10px; width: 33%; }
        .terms strong { color: {{ $primary }}; display: block; margin-bottom: 3px; text-transform: uppercase; }
    </style>
</head>
<body>
<div class="page">
    <table class="hero" cellspacing="0" cellpadding="0">
        <tr>
            <td class="brand">
                <img class="logo" src="{{ $companyLogo }}" alt="Logo">
                <div class="brand-name">{{ $setting('company_name', getSettingValue('company_name')) }}</div>
                <div class="tagline">{{ $setting('business_trade_name', 'Calidad y confianza para tu vehiculo') }}</div>
            </td>
            <td class="center">
                <div class="contact-line"><span class="contact-icon"></span><span class="contact-label">Tel.</span><span class="contact-value">{{ $setting('phone', getSettingValue('phone')) }} {{ $setting('business_phone_secondary') }}</span></div>
                <div class="contact-line"><span class="contact-icon"></span><span class="contact-label">WhatsApp</span><span class="contact-value">{{ $setting('business_whatsapp', $setting('phone', getSettingValue('phone'))) }}</span></div>
                <div class="contact-line"><span class="contact-icon"></span><span class="contact-label">Correo</span><span class="contact-value">{{ $setting('business_emails', $setting('email', getSettingValue('email'))) }}</span></div>
                <div class="contact-line"><span class="contact-icon"></span><span class="contact-label">Direccion</span><span class="contact-value">{{ $setting('address', getSettingValue('address')) }} {{ $setting('city') }}</span></div>
                <div class="contact-line"><span class="contact-icon"></span><span class="contact-label">Redes</span><span class="contact-value">{{ $socialText ?: 'N/A' }}</span></div>
            </td>
            <td class="qr">
                @if($qr)<img src="{{ $qr }}" alt="QR"><div>Escanea para contactarnos</div>@endif
            </td>
            <td class="quote-side">
                <div class="label">Cotizacion No.</div>
                <div class="number">{{ $quotation->reference_code }}</div>
                <div class="status {{ $statusClass }}">{{ $statusLabel }}</div>
                <div class="date-line">Fecha: {{ $createdAt->format('d/m/Y') }}</div>
                <div class="date-line">Hora: {{ $createdAt->format('h:i A') }}</div>
            </td>
        </tr>
    </table>

    <table class="strip">
        <tr>
            <td><strong>RUC</strong>{{ $setting('business_ruc', 'N/A') }}</td>
            <td><strong>Telefonos</strong>{{ $setting('phone', getSettingValue('phone')) }} {{ $setting('business_phone_secondary') }}</td>
            <td><strong>Correos</strong>{{ $setting('business_emails', $setting('email', getSettingValue('email'))) }}</td>
            <td><strong>Direccion</strong>{{ $setting('address', getSettingValue('address')) }} {{ $setting('city') }}</td>
            <td><strong>WhatsApp</strong>{{ $setting('business_whatsapp', $setting('phone', getSettingValue('phone'))) }}</td>
            <td><strong>Redes</strong>{{ $socialText ?: 'N/A' }}</td>
        </tr>
    </table>

    <table class="cards" cellspacing="0" cellpadding="0">
        <tr>
            <td class="card">
                <div class="card-title">Informacion del cliente</div>
                <table class="metric" width="100%">
                    <tr>
                        <td><strong>Nombre</strong><span>{{ $quotation->customer->name ?? 'N/A' }}</span></td>
                        <td><strong>Telefono</strong><span>{{ $quotation->customer->phone ?? 'N/A' }}</span></td>
                        <td><strong>Cedula</strong><span>{{ $quotation->customer->cedula ?? ($quotation->customer->ruc ?? ($quotation->customer->identification ?? 'N/A')) }}</span></td>
                    </tr>
                </table>
            </td>
            <td width="2%"></td>
            <td class="card">
                <div class="card-title">Informacion de la cotizacion</div>
                <table class="metric" width="100%">
                    <tr>
                        <td><strong>Fecha</strong><span>{{ $createdAt->format('d/m/Y') }}</span></td>
                        <td><strong>Hora</strong><span>{{ $createdAt->format('h:i A') }}</span></td>
                        <td><strong>Vendedor</strong><span>{{ $sellerName ?: 'N/A' }}</span></td>
                        <td><strong>Vencimiento</strong><span>{{ $expiresAt->format('d/m/Y') }} ({{ $validityDays }} dias)</span></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
        <tr>
            <th width="10%">Codigo</th>
            @if($showProductImage)<th width="10%">Imagen</th>@endif
            <th>Producto</th>
            <th width="11%">Marca</th>
            <th width="7%">Cant.</th>
            <th width="12%">Precio unitario</th>
            <th width="10%">Descuento</th>
            <th width="10%">Impuesto</th>
            <th width="12%">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($quotation->quotationItems as $quotationItem)
            @php
                $product = $quotationItem->product;
                $image = str_replace('\\', '/', optional($product)->quotation_image_url ?: $firstImage([
                    optional($product)->image_url,
                    optional(optional($product)->mainProduct)->image_url,
                    optional($product)->getMedia(\App\Models\Product::PATH)->toArray(),
                    optional(optional($product)->mainProduct)->getMedia(\App\Models\MainProduct::PATH)->toArray(),
                ]) ?: ($defaultProductImage ?? asset('images/no-image.png')));
            @endphp
            <tr>
                <td><span class="code">{{ $product->code ?? $product->product_code ?? '-' }}</span></td>
                @if($showProductImage)<td><img class="product-img" src="{{ $image }}" alt="Producto"></td>@endif
                <td class="name"><strong>{{ $product->name ?? 'Producto' }}</strong></td>
                <td>{{ optional(optional($product)->brand)->name ?? '-' }}</td>
                <td>{{ $quotationItem->quantity }}</td>
                <td>{{ currencyAlignment(number_format((float) $quotationItem->product_price, 2)) }}</td>
                <td>{{ currencyAlignment(number_format((float) $quotationItem->discount_amount, 2)) }}</td>
                <td>{{ currencyAlignment(number_format((float) $quotationItem->tax_amount, 2)) }}</td>
                <td><strong>{{ currencyAlignment(number_format((float) $quotationItem->sub_total, 2)) }}</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="bottom" cellspacing="0" cellpadding="0">
        <tr>
            <td class="notes">
                <div class="card-title">Observaciones</div>
                <p>{{ $setting('quotation_validity_text', 'Esta cotizacion es valida durante los dias indicados.') }}</p>
                <p>{{ $setting('quotation_auto_observations', $quotation->note ?? '') }}</p>
                <div class="thanks">
                    <strong>{{ $setting('quotation_thank_you_text', 'Gracias por confiar en nosotros.') }}</strong>
                    <p>{{ $setting('quotation_footer_text', 'Soluciones automotrices profesionales para su negocio.') }}</p>
                    <p>{{ $socialText }}</p>
                </div>
            </td>
            <td width="2%"></td>
            <td class="total-cell">
                <table class="totals" width="100%">
                    <tr><td class="label">Subtotal</td><td class="amount">{{ currencyAlignment(number_format($subtotal, 2)) }}</td></tr>
                    <tr><td class="label">IVA ({{ number_format((float) $quotation->tax_rate, 2) }}%)</td><td class="amount">{{ currencyAlignment(number_format((float) $quotation->tax_amount, 2)) }}</td></tr>
                    <tr><td class="label">Descuento</td><td class="amount">{{ currencyAlignment(number_format((float) $quotation->discount, 2)) }}</td></tr>
                    <tr><td class="label">Transporte</td><td class="amount">{{ currencyAlignment(number_format((float) $quotation->shipping, 2)) }}</td></tr>
                    <tr class="grand"><td>Total final</td><td class="amount">{{ currencyAlignment(number_format((float) $quotation->grand_total, 2)) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="footer" cellspacing="0" cellpadding="0">
        <tr>
            <td class="benefits">
                <table width="100%">
                    <tr>
                        @foreach($benefits as $benefit)
                            <td>
                                @if(! empty($benefit['image']))<img class="benefit-img" src="{{ str_replace('\\', '/', $benefit['image']) }}" alt="{{ $benefit['title'] }}">@endif
                                {{ $benefit['title'] }}<span>{{ $benefit['subtitle'] }}</span>
                            </td>
                        @endforeach
                    </tr>
                </table>
            </td>
            <td class="marks">
                @if($setting('quotation_digital_signature'))<img class="mark-img" src="{{ $setting('quotation_digital_signature') }}" alt="Firma">@endif
                @if($setting('quotation_company_stamp'))<img class="mark-img" src="{{ $setting('quotation_company_stamp') }}" alt="Sello">@endif
            </td>
            <td class="terms">
                <strong>Terminos y condiciones</strong>
                <div>{{ $setting('business_terms', 'Esta cotizacion tiene validez segun los dias indicados. Los precios incluyen IVA y estan sujetos a disponibilidad.') }}</div>
                <div>{{ $setting('business_return_policy') }}</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
