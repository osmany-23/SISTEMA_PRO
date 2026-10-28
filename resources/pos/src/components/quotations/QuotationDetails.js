import React, { useEffect, useMemo } from "react";
import { connect } from "react-redux";
import { Table } from "react-bootstrap-v5";
import { useParams } from "react-router-dom";
import MasterLayout from "../MasterLayout";
import HeaderTitle from "../header/HeaderTitle";
import TabTitle from "../../shared/tab-title/TabTitle";
import {
    currencySymbolHandling,
    formatNumber,
    formatQuantityAuto,
    getFormattedMessage,
    parseNumber,
    placeholderText,
} from "../../shared/sharedMethod";
import { fetchFrontSetting } from "../../store/action/frontSettingAction";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
    faCalendarAlt,
    faClock,
    faCommentDots,
    faEnvelope,
    faFilePdf,
    faGlobe,
    faLocationDot,
    faMobileAlt,
    faPrint,
    faShareNodes,
    faShieldAlt,
    faTag,
    faTruckFast,
    faUser,
    faWrench,
} from "@fortawesome/free-solid-svg-icons";
import TopProgressBar from "../../shared/components/loaders/TopProgressBar";
import { quotationDetailsAction } from "../../store/action/quotationDetails";
import { quotationPdfAction } from "../../store/action/quotationPdfAction";
import user from "../../assets/images/brand_logo.png";
import noImage from "../../assets/images/no-image.png";

const safeValue = (value, fallback = "N/A") =>
    value !== undefined && value !== null && value !== "" ? value : fallback;

const compactText = (...values) =>
    values.filter((value) => value !== undefined && value !== null && value !== "").join(" ");

const normalizeImageUrl = (value) =>
    typeof value === "string" ? value.replace(/\\/g, "/") : value;

const parseSafeDate = (value, fallback = new Date()) => {
    if (!value) {
        return fallback;
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? fallback : date;
};

const addDays = (date, days) => {
    const nextDate = new Date(date);
    nextDate.setDate(nextDate.getDate() + days);

    return nextDate;
};

const findImageUrl = (value) => {
    if (!value) {
        return "";
    }
    if (typeof value === "string") {
        return normalizeImageUrl(value);
    }
    if (Array.isArray(value)) {
        return value.map(findImageUrl).find(Boolean) || "";
    }
    if (typeof value === "object") {
        if (typeof value.url === "string") {
            return value.url;
        }
        if (typeof value.original_url === "string") {
            return value.original_url;
        }
        if (Array.isArray(value.imageUrls)) {
            return value.imageUrls.find(Boolean) || "";
        }
        return Object.values(value).map(findImageUrl).find(Boolean) || "";
    }

    return "";
};

const getProductImage = (product) => {
    return normalizeImageUrl(product?.quotation_image_url) || findImageUrl([
        product?.images,
        product?.image_url,
        product?.media,
        product?.main_product?.image_url,
        product?.main_product?.images,
        product?.main_product?.media,
        product?.mainProduct?.image_url,
        product?.mainProduct?.images,
        product?.mainProduct?.media,
    ]) || noImage;
};

const buildSocialList = (companyInfo = {}) =>
    [
        ["Facebook", companyInfo.business_facebook],
        ["Instagram", companyInfo.business_instagram],
        ["TikTok", companyInfo.business_tiktok],
        ["LinkedIn", companyInfo.business_linkedin],
        ["YouTube", companyInfo.business_youtube],
    ].filter(([, value]) => value);

const buildSocialText = (socialList = [], fallback = "") =>
    socialList.length ? socialList.map(([name, value]) => `${name}: ${value}`).join(" | ") : safeValue(fallback);

const ContactRow = ({ icon, label, value }) => (
    <div className="quote-contact-row">
        <span className="quote-contact-row__icon">
            <FontAwesomeIcon icon={icon} />
        </span>
        <strong>{label}</strong>
        <span className="quote-contact-row__value">{safeValue(value)}</span>
    </div>
);

const getStatusMeta = (quotationDetails) => {
    const status = Number(quotationDetails?.status);
    const statusClass = quotationDetails?.status_class === "sent" ? "sent" : "pending";
    const statusLabel = quotationDetails?.status_label;

    if (statusLabel) {
        return { label: statusLabel, className: `quote-status quote-status--${statusClass}` };
    }

    if (status === 1) {
        return { label: "Enviado", className: "quote-status quote-status--sent" };
    }
    if (status === 2) {
        return { label: "Pendiente", className: "quote-status quote-status--pending" };
    }
    if (status === 3) {
        return { label: "Rechazada", className: "quote-status quote-status--rejected" };
    }
    return { label: "Pendiente", className: "quote-status quote-status--pending" };
};

const getValidityMeta = (isExpired) =>
    isExpired
        ? { label: "Validez vencida", className: "quote-validity quote-validity--expired" }
        : { label: "Validez vigente", className: "quote-validity quote-validity--active" };

const buildBenefits = (companyInfo = {}) => [
    {
        title: companyInfo.quotation_benefit_1_title || "Repuestos originales",
        subtitle: companyInfo.quotation_benefit_1_subtitle || "Calidad garantizada",
        image: companyInfo.quotation_benefit_1_image,
        icon: faShieldAlt,
    },
    {
        title: companyInfo.quotation_benefit_2_title || "Mejores precios",
        subtitle: companyInfo.quotation_benefit_2_subtitle || "Competitivos",
        image: companyInfo.quotation_benefit_2_image,
        icon: faTag,
    },
    {
        title: companyInfo.quotation_benefit_3_title || "Asesoria profesional",
        subtitle: companyInfo.quotation_benefit_3_subtitle || "Te ayudamos a elegir",
        image: companyInfo.quotation_benefit_3_image,
        icon: faWrench,
    },
    {
        title: companyInfo.quotation_benefit_4_title || "Entrega rapida",
        subtitle: companyInfo.quotation_benefit_4_subtitle || "En Jalapa y alrededores",
        image: companyInfo.quotation_benefit_4_image,
        icon: faTruckFast,
    },
];

const formatDate = (date) =>
    parseSafeDate(date).toLocaleDateString("es-NI", { day: "2-digit", month: "2-digit", year: "numeric" });

const formatTime = (date) =>
    parseSafeDate(date).toLocaleTimeString("es-NI", { hour: "2-digit", minute: "2-digit" });

const QuotationDetails = (props) => {
    const {
        quotationDetailsAction,
        quotationDetails,
        fetchFrontSetting,
        frontSetting,
        allConfigData,
        quotationPdfAction,
    } = props;
    const { id } = useParams();

    useEffect(() => {
        fetchFrontSetting();
        quotationDetailsAction(id);
    }, []);

    const companyInfo = {
        ...(quotationDetails?.company_info || {}),
        ...(frontSetting?.value || {}),
    };
    const currencySymbol = frontSetting?.value?.currency_symbol;
    const statusMeta = getStatusMeta(quotationDetails);
    const brandColor = companyInfo.business_primary_color || "#c1121f";
    const headerColor = companyInfo.business_header_color || "#111827";
    const tableColor = companyInfo.business_table_color || "#1f2937";
    const validityDays = Math.max(
        Number(quotationDetails?.validity_days || companyInfo.quotation_validity_days || 15),
        1
    );
    const createdDate = parseSafeDate(
        quotationDetails?.issued_at || quotationDetails?.created_at || quotationDetails?.date,
        new Date()
    );
    const expiryDate = useMemo(() => {
        return parseSafeDate(quotationDetails?.expires_at, addDays(createdDate, validityDays));
    }, [quotationDetails?.expires_at, quotationDetails?.issued_at, quotationDetails?.created_at, quotationDetails?.date, validityDays]);
    const validityMeta = getValidityMeta(Boolean(quotationDetails?.is_expired));
    const socialList = buildSocialList(companyInfo);
    const socialText = buildSocialText(socialList, companyInfo.business_website);
    const businessAddress = compactText(companyInfo.address, companyInfo.city);
    const businessPhones = compactText(companyInfo.phone, companyInfo.business_phone_secondary ? `/ ${companyInfo.business_phone_secondary}` : "");
    const benefits = buildBenefits(companyInfo);
    const showProductImage = companyInfo.quotation_show_product_image !== "0" && companyInfo.quotation_show_product_image !== false;
    const customer = quotationDetails?.customer || {};
    const products = quotationDetails?.quotation_items || [];

    const money = (amount) =>
        currencySymbolHandling(allConfigData, currencySymbol, amount || "0.00");

    const shareText = `Cotizacion ${quotationDetails?.reference_code || ""} - ${safeValue(
        companyInfo.company_name,
        "Empresa"
    )}. Total: ${money(quotationDetails?.grand_total)}. Valida hasta ${formatDate(expiryDate)}.`;

    const onShareWhatsApp = () => {
        const phone = String(companyInfo.business_whatsapp || companyInfo.phone || "").replace(/\D/g, "");
        window.open(`https://wa.me/${phone}?text=${encodeURIComponent(shareText)}`, "_blank");
    };

    const onShareMail = () => {
        const subject = `Cotizacion ${quotationDetails?.reference_code || ""}`;
        window.location.href = `mailto:${customer.email || ""}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(shareText)}`;
    };

    return (
        <MasterLayout>
            <TopProgressBar />
            <HeaderTitle title={getFormattedMessage("details-quotations.title")} to="/app/quotations" />
            <TabTitle title={placeholderText("details-quotations.title")} />

            <div
                className="quotation-premium"
                style={{
                    "--quote-primary": brandColor,
                    "--quote-header": headerColor,
                    "--quote-table": tableColor,
                }}
            >
                <div className="quotation-premium__actions">
                    <button className="btn btn-primary" onClick={() => quotationPdfAction(id)}>
                        <FontAwesomeIcon icon={faFilePdf} className="me-2" />
                        Descargar PDF
                    </button>
                    <button className="btn btn-outline-dark" onClick={() => window.print()}>
                        <FontAwesomeIcon icon={faPrint} className="me-2" />
                        Imprimir
                    </button>
                    <button className="btn btn-outline-success" onClick={onShareWhatsApp}>
                        <FontAwesomeIcon icon={faShareNodes} className="me-2" />
                        WhatsApp
                    </button>
                    <button className="btn btn-outline-secondary" onClick={onShareMail}>
                        <FontAwesomeIcon icon={faEnvelope} className="me-2" />
                        Correo
                    </button>
                </div>

                <section className="quotation-premium__hero">
                    <div className="quotation-premium__brand">
                        <img src={companyInfo.logo || frontSetting?.value?.logo || user} alt="Logo" />
                        <div>
                            <span>Cotizacion corporativa</span>
                            <h1>{safeValue(companyInfo.company_name)}</h1>
                            <p>{safeValue(companyInfo.business_trade_name, "Calidad y confianza para tu vehiculo")}</p>
                        </div>
                    </div>
                    <div className="quotation-premium__hero-contact">
                        <ContactRow icon={faMobileAlt} label="Tel." value={companyInfo.phone} />
                        <ContactRow icon={faCommentDots} label="WhatsApp" value={companyInfo.business_whatsapp || companyInfo.business_phone_secondary || companyInfo.phone} />
                        <ContactRow icon={faEnvelope} label="Correo" value={companyInfo.business_emails || companyInfo.email} />
                        <ContactRow icon={faLocationDot} label="Direccion" value={businessAddress} />
                        {socialList.length || companyInfo.business_website ? <ContactRow icon={faGlobe} label="Redes" value={socialText} /> : null}
                    </div>
                    {companyInfo.quotation_company_qr ? (
                        <div className="quotation-premium__qr">
                            <img src={companyInfo.quotation_company_qr} alt="QR empresarial" />
                            <span>Escanea para contactarnos</span>
                        </div>
                    ) : null}
                    <div className="quotation-premium__number">
                        <span>Cotizacion No.</span>
                        <strong>{safeValue(quotationDetails?.reference_code)}</strong>
                        <em className={statusMeta.className}>{statusMeta.label}</em>
                        <em className={validityMeta.className}>{validityMeta.label}</em>
                        <small><FontAwesomeIcon icon={faCalendarAlt} /> {formatDate(createdDate)}</small>
                        <small><FontAwesomeIcon icon={faClock} /> {formatTime(createdDate)}</small>
                    </div>
                </section>

                <section className="quotation-premium__contact">
                    <ContactRow icon={faTag} label="RUC" value={companyInfo.business_ruc} />
                    <ContactRow icon={faMobileAlt} label="Telefonos" value={businessPhones} />
                    <ContactRow icon={faEnvelope} label="Correos" value={companyInfo.business_emails || companyInfo.email} />
                    <ContactRow icon={faCommentDots} label="WhatsApp" value={companyInfo.business_whatsapp || companyInfo.phone} />
                    <ContactRow icon={faLocationDot} label="Direccion" value={businessAddress} />
                    <ContactRow icon={faGlobe} label="Redes" value={socialText} />
                </section>

                <section className="quotation-premium__grid">
                    <article className="quote-card">
                        <h3><FontAwesomeIcon icon={faUser} /> Informacion del cliente</h3>
                        <div className="quote-card__metrics">
                            <div><strong>Nombre</strong><span>{safeValue(customer.name)}</span></div>
                            <div><strong>Telefono</strong><span>{safeValue(customer.phone)}</span></div>
                            <div><strong>Cedula</strong><span>{safeValue(customer.cedula || customer.ruc || customer.identification)}</span></div>
                        </div>
                    </article>

                    <article className="quote-card">
                        <h3><FontAwesomeIcon icon={faFilePdf} /> Informacion de la cotizacion</h3>
                        <div className="quote-card__metrics">
                            <div><strong>Fecha</strong><span>{formatDate(createdDate)}</span></div>
                            <div><strong>Hora</strong><span>{formatTime(createdDate)}</span></div>
                            <div><strong>Vendedor</strong><span>{safeValue(quotationDetails?.seller_name || quotationDetails?.user?.name || quotationDetails?.user_name, "N/A")}</span></div>
                            <div><strong>Vencimiento</strong><span>{formatDate(expiryDate)} <em>({validityDays} dias)</em></span></div>
                        </div>
                    </article>
                </section>

                <section className="quotation-premium__table">
                    <Table responsive>
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                {showProductImage ? <th>Imagen</th> : null}
                                <th>Producto</th>
                                <th>Marca</th>
                                <th>Cant.</th>
                                <th>Precio unitario</th>
                                <th>Descuento</th>
                                <th>Impuesto</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.map((details, index) => {
                                const product = details.product || {};
                                return (
                                    <tr key={index}>
                                        <td><span className="quote-code">{product.code || product.product_code || "-"}</span></td>
                                        {showProductImage ? (
                                            <td>
                                                <img
                                                    className="quote-product-img"
                                                    src={getProductImage(product)}
                                                    alt={product.name || "Producto"}
                                                    onError={(event) => {
                                                        event.currentTarget.src = noImage;
                                                    }}
                                                />
                                            </td>
                                        ) : null}
                                        <td className="quote-product-name">
                                            <strong>{safeValue(product.name, "Producto")}</strong>
                                            {details.lote_codigo || details.codigo_lote_sistema ? (
                                                <small>Lote: {details.lote_codigo || details.codigo_lote_sistema}</small>
                                            ) : null}
                                        </td>
                                        <td>{safeValue(product.brand_name || product.brand?.name, "-")}</td>
                                        <td>{formatQuantityAuto(details.quantity)}</td>
                                        <td>{money(details.product_price || details.net_unit_price)}</td>
                                        <td>{money(details.discount_amount)}</td>
                                        <td>{money(details.tax_amount)}</td>
                                        <td><strong>{money(details.sub_total)}</strong></td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </Table>
                </section>

                <section className="quotation-premium__bottom">
                    <article className="quote-notes">
                        <h3><FontAwesomeIcon icon={faCommentDots} /> Observaciones</h3>
                        <p>{safeValue(companyInfo.quotation_validity_text, "Cotizacion sujeta a disponibilidad.")}</p>
                        <p>{safeValue(companyInfo.quotation_auto_observations, quotationDetails?.note || "")}</p>
                        <p>{safeValue(companyInfo.business_terms, "")}</p>
                        <div className="quote-socials">
                            <strong>{safeValue(companyInfo.quotation_thank_you_text, "Gracias por confiar en nosotros.")}</strong>
                            {socialList.length || companyInfo.business_website ? <span>{socialText}</span> : null}
                        </div>
                    </article>
                    <article className="quote-totals">
                        <div><span>Subtotal</span><strong>{money(quotationDetails?.subtotal ?? (parseNumber(quotationDetails?.grand_total, 0) - parseNumber(quotationDetails?.tax_amount, 0) + parseNumber(quotationDetails?.discount, 0) - parseNumber(quotationDetails?.shipping, 0)))}</strong></div>
                        <div><span>IVA</span><strong>{money(quotationDetails?.tax_amount)} ({formatNumber(parseNumber(quotationDetails?.tax_rate, 0), 2)}%)</strong></div>
                        <div><span>Descuento</span><strong>{money(quotationDetails?.discount)}</strong></div>
                        <div><span>Transporte</span><strong>{money(quotationDetails?.shipping)}</strong></div>
                        <div className="quote-totals__grand"><span>Total final</span><strong>{money(quotationDetails?.grand_total)}</strong></div>
                    </article>
                </section>

                <footer className="quotation-premium__footer">
                    <div className="quotation-premium__benefits">
                        {benefits.map((benefit, index) => (
                            <div className="quote-benefit" key={index}>
                                <span className="quote-benefit__media">
                                    {benefit.image ? <img src={normalizeImageUrl(benefit.image)} alt={benefit.title} /> : <FontAwesomeIcon icon={benefit.icon} />}
                                </span>
                                <strong>{benefit.title}</strong>
                                <span>{benefit.subtitle}</span>
                            </div>
                        ))}
                    </div>
                    <div className="quotation-premium__terms">
                        <strong>Terminos y condiciones</strong>
                        <p>{safeValue(companyInfo.quotation_footer_text, companyInfo.business_legal_text || "")}</p>
                        <p>{safeValue(companyInfo.business_return_policy, "")}</p>
                    </div>
                    <div className="quotation-premium__marks">
                        {companyInfo.quotation_digital_signature ? <img src={companyInfo.quotation_digital_signature} alt="Firma" /> : null}
                        {companyInfo.quotation_company_stamp ? <img src={companyInfo.quotation_company_stamp} alt="Sello" /> : null}
                        {companyInfo.quotation_company_qr ? <img src={companyInfo.quotation_company_qr} alt="QR" /> : null}
                    </div>
                </footer>
            </div>
        </MasterLayout>
    );
};

const mapStateToProps = (state) => {
    const { quotationDetails, frontSetting, allConfigData } = state;
    return { quotationDetails, frontSetting, allConfigData };
};

export default connect(mapStateToProps, {
    quotationDetailsAction,
    fetchFrontSetting,
    quotationPdfAction,
})(QuotationDetails);
