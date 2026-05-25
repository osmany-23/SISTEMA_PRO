import React, { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useDispatch, useSelector } from "react-redux";
import { Image } from "react-bootstrap-v5";
import * as EmailValidator from "email-validator";
import { loginAction } from "../../store/action/authAction";
import TabTitle from "../../shared/tab-title/TabTitle";
import { fetchFrontSetting } from "../../store/action/frontSettingAction";
import { Tokens } from "../../constants";
import {
    getFormattedMessage,
    placeholderText,
    translateMessage,
} from "../../shared/sharedMethod";
import AppBootstrapLoader from "../../shared/components/loaders/AppBootstrapLoader";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faEye, faEyeSlash, faArrowRight, faShieldAlt, faChartLine, faCubes, faFileInvoice, faLock, faEnvelope } from "@fortawesome/free-solid-svg-icons";
import "../../assets/scss/components/login-modern.scss";

const REMEMBER_LOGIN_KEY = "inventapro_login_remember";
const REMEMBER_EMAIL_KEY = "inventapro_login_email";

const Login = () => {
    const navigate = useNavigate();
    const dispatch = useDispatch();
    const { frontSetting } = useSelector((state) => state);
    const [showPassword, setShowPassword] = useState(false);
    const [loading, setLoading] = useState(false);
    const token = localStorage.getItem(Tokens.ADMIN);
    const [rememberMe, setRememberMe] = useState(false);

    const [loginInputs, setLoginInputs] = useState({
        email: "",
        password: "",
    });

    useEffect(() => {
        dispatch(fetchFrontSetting());
    }, [dispatch]);

    useEffect(() => {
        const remembered = localStorage.getItem(REMEMBER_LOGIN_KEY) === "true";
        const rememberedEmail = localStorage.getItem(REMEMBER_EMAIL_KEY) || "";

        if (remembered && rememberedEmail) {
            setRememberMe(true);
            setLoginInputs((inputs) => ({
                ...inputs,
                email: rememberedEmail,
            }));
        }
    }, []);

    useEffect(() => {
        if (token && !loading) {
            navigate("/app/dashboard");
        }
    }, [loading, navigate, token]);

    const [errors, setErrors] = useState({
        email: "",
        password: "",
    });

    const handleValidation = () => {
        const newErrors = {
            email: "",
            password: "",
        };
        let isValid = false;
        if (!EmailValidator.validate(loginInputs["email"])) {
            if (!loginInputs["email"]) {
                newErrors.email = getFormattedMessage(
                    "globally.input.email.validate.label"
                );
            } else {
                newErrors.email = getFormattedMessage(
                    "globally.input.email.valid.validate.label"
                );
            }
        } else if (!loginInputs["password"]) {
            newErrors.password = getFormattedMessage(
                "user.input.password.validate.label"
            );
        } else {
            isValid = true;
        }
        setErrors(newErrors);
        setLoading(false);
        return isValid;
    };

    const prepareFormData = () => {
        const formData = new FormData();
        formData.append("email", loginInputs.email);
        formData.append("password", loginInputs.password);
        formData.append(
            "language_code",
            localStorage.getItem("updated_language")
        );
        return formData;
    };

    const onLogin = (e) => {
        e.preventDefault();
        const valid = handleValidation();
        if (valid) {
            setLoading(true);
            if (rememberMe) {
                localStorage.setItem(REMEMBER_LOGIN_KEY, "true");
                localStorage.setItem(REMEMBER_EMAIL_KEY, loginInputs.email.trim());
            } else {
                localStorage.removeItem(REMEMBER_LOGIN_KEY);
                localStorage.removeItem(REMEMBER_EMAIL_KEY);
            }
            dispatch(
                loginAction(prepareFormData(loginInputs), navigate, setLoading)
            );
        }
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setLoginInputs((inputs) => ({
            ...inputs,
            [name]: value,
        }));
        setErrors((prev) => ({
            ...prev,
            [name]: "",
        }));
    };

    const handleHideShowPassword = () => {
        setShowPassword(!showPassword);
    };

    const companyName = frontSetting?.value?.company_name || "InventaPro";
    const companyLogo = frontSetting?.value?.logo;
    const rememberMeLabel = translateMessage(
        "globally.remember.me.label",
        "Recordarme"
    );

    if (loading) {
        return <AppBootstrapLoader variant="fullscreen" />;
    }

    return (
        <div className="login-container">
            <TabTitle
                title={placeholderText("login-form.login-btn.label")}
            />
            
            <div className="login-wrapper">
                {/* Panel Izquierdo - Branding */}
                <div className="login-panel-left">
                    {/* Efectos de fondo */}
                    <div className="login-bg-shapes">
                        <div className="shape shape-1"></div>
                        <div className="shape shape-2"></div>
                        <div className="shape shape-3"></div>
                    </div>

                    {/* Logo y Branding */}
                    <div className="login-branding">
                        {companyLogo && (
                            <div className="login-logo-wrapper">
                                <Image
                                    src={companyLogo}
                                    alt="Company Logo"
                                    className="login-logo"
                                />
                            </div>
                        )}
                        
                        <h1 className="login-title">
                            {getFormattedMessage("login-form.title") || "Sistema de Facturación"}
                        </h1>
                        <p className="login-subtitle">
                            Gestiona tu negocio de forma eficiente, controla inventario y aumenta tus ventas.
                        </p>
                    </div>

                    {/* Tarjetas de Características */}
                    <div className="login-features">
                        <div className="feature-card">
                            <div className="feature-icon">
                                <FontAwesomeIcon icon={faChartLine} />
                            </div>
                            <div className="feature-text">
                                <span className="feature-label">Ventas</span>
                                <span className="feature-desc">Control total</span>
                            </div>
                        </div>

                        <div className="feature-card">
                            <div className="feature-icon">
                                <FontAwesomeIcon icon={faCubes} />
                            </div>
                            <div className="feature-text">
                                <span className="feature-label">Inventario</span>
                                <span className="feature-desc">Stock en tiempo real</span>
                            </div>
                        </div>

                        <div className="feature-card">
                            <div className="feature-icon">
                                <FontAwesomeIcon icon={faFileInvoice} />
                            </div>
                            <div className="feature-text">
                                <span className="feature-label">Facturación</span>
                                <span className="feature-desc">Rápida y segura</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Panel Derecho - Formulario */}
                <div className="login-panel-right">
                    <div className="login-form-wrapper">
                        {/* Encabezado del Formulario */}
                        <div className="login-form-header">
                            {companyLogo && (
                                <div className="login-mobile-logo-wrapper">
                                    <Image
                                        src={companyLogo}
                                        alt="Company Logo"
                                        className="login-mobile-logo"
                                    />
                                </div>
                            )}
                            <h2 className="login-form-title">
                                {getFormattedMessage("login-form.title")}
                            </h2>
                            <p className="login-form-subtitle">
                                Bienvenido a <span className="company-name">{companyName}</span>
                            </p>
                        </div>

                        {/* Formulario */}
                        <form onSubmit={onLogin} className="login-form">
                            {/* Email Input */}
                            <div className="login-form-group">
                                <div className="login-input-wrapper">
                                    <input
                                        type="email"
                                        name="email"
                                        placeholder={placeholderText(
                                            "globally.input.email.placeholder.label"
                                        )}
                                        value={loginInputs.email}
                                        onChange={(e) => handleChange(e)}
                                        className={`login-input ${errors["email"] ? "error" : ""}`}
                                        autoComplete="email"
                                        required
                                    />
                                    <span className="login-input-icon">
                                        <FontAwesomeIcon icon={faEnvelope} />
                                    </span>
                                </div>
                                {errors.email && (
                                    <span className="login-error-text">
                                        {errors["email"]}
                                    </span>
                                )}
                            </div>

                            {/* Password Input */}
                            <div className="login-form-group">
                                <div className="login-input-wrapper">
                                    <span className="login-input-icon">
                                        <FontAwesomeIcon icon={faLock} />
                                    </span>
                                    <input
                                        type={showPassword ? "text" : "password"}
                                        name="password"
                                        placeholder={placeholderText(
                                            "user.input.password.placeholder.label"
                                        )}
                                        value={loginInputs.password}
                                        onChange={(e) => handleChange(e)}
                                        className={`login-input ${errors["password"] ? "error" : ""}`}
                                        autoComplete="current-password"
                                        required
                                    />
                                    <span
                                        className="login-input-icon-password"
                                        onClick={handleHideShowPassword}
                                    >
                                        <FontAwesomeIcon
                                            icon={showPassword ? faEye : faEyeSlash}
                                        />
                                    </span>
                                </div>
                                {errors.password && (
                                    <span className="login-error-text">
                                        {errors["password"]}
                                    </span>
                                )}
                            </div>

                            {/* Remember Me & Forgot Password */}
                            <div className="login-form-options">
                                <label className="login-remember">
                                    <input
                                        type="checkbox"
                                        checked={rememberMe}
                                        onChange={(e) => setRememberMe(e.target.checked)}
                                    />
                                    <span>{rememberMeLabel}</span>
                                </label>
                                <Link
                                    to="/forgot-password"
                                    className="login-forgot-link"
                                >
                                    {getFormattedMessage(
                                        "login-form.forgot-password.label"
                                    )}
                                </Link>
                            </div>

                            {/* Submit Button */}
                            <button
                                type="submit"
                                className={`login-btn-submit ${loading ? "loading" : ""}`}
                                disabled={loading}
                            >
                                <span className="btn-text">
                                    {loading
                                        ? getFormattedMessage("globally.loading.label")
                                        : getFormattedMessage("login-form.login-btn.label")}
                                </span>
                                {!loading && (
                                    <FontAwesomeIcon
                                        icon={faArrowRight}
                                        className="btn-icon"
                                    />
                                )}
                            </button>
                        </form>

                        {/* Footer Security */}
                        <div className="login-form-footer">
                            <FontAwesomeIcon icon={faShieldAlt} className="footer-icon" />
                            <span className="footer-text">Seguro, rápido y confiable</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Login;
