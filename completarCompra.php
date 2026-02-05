<?php
/**
 * completarCompra.php - Perpetualife
 * Checkout Final: Persistencia + Cupones + Envío Dinámico + UX (Sin eliminar nada)
 */
require_once 'api/conexion.php'; 
?>
<!DOCTYPE html>
<html lang="es" x-data="checkoutData()" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra | Perpetualife</title>
    <link rel="icon" type="image/png" href="imagenes/monito01.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://www.paypal.com/sdk/js?client-id=AR2yUZcO674dQIfZR6ks44WIf_rq5oLHx9adznrIKHOo5J6qcrKuoGURz3pnSYQwUjBEjEasWT5GWpSW&currency=MXN"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'perpetua-blue': '#1e3a8a',
                        'perpetua-aqua': '#22d3ee',
                    }
                }
            }
        }

        function checkoutData() {
            return {
                darkMode: localStorage.getItem('darkMode') === 'true',
                lang: localStorage.getItem('lang') || 'es',
                cart: JSON.parse(localStorage.getItem('cart')) || [],
                
                cliente: { nombre: '', email: '', telefono: '', direccion: '', estado: '' }, // Agregado 'estado'
                
                // Lista de Estados para el Selector
                estadosMx: ["Aguascalientes", "Baja California", "Baja California Sur", "Campeche", "Chiapas", "Chihuahua", "Ciudad de México", "Coahuila", "Colima", "Durango", "Estado de México", "Guanajuato", "Guerrero", "Hidalgo", "Jalisco", "Michoacán", "Morelos", "Nayarit", "Nuevo León", "Oaxaca", "Puebla", "Querétaro", "Quintana Roo", "San Luis Potosí", "Sinaloa", "Sonora", "Tabasco", "Tamaulipas", "Tlaxcala", "Veracruz", "Yucatán", "Zacatecas"],

                userLoggedIn: false, 

                showLoginModal: false,
                loginView: 'login',
                loginData: { email: '', password: '' },
                loginError: '',
                recEmail: '',
                recMsg: '',
                
                crearCuenta: false,
                newAccount: { password: '', confirm: '' },

                couponCode: '',
                discount: 0,
                couponApplied: false,
                couponMsg: '',
                shippingFree: false, 
                shippingCost: 150, // Inicializado con Zona Local

                isProcessing: false,

                t: {
                    es: {
                        back: 'Volver a la tienda',
                        clientQuestion: '¿Ya eres cliente?', 
                        clientDesc: 'Inicia sesión para cargar tus datos automáticamente.',
                        loginBtn: 'Iniciar Sesión',
                        welcome: 'Bienvenido', email: 'Correo Electrónico', pass: 'Contraseña',
                        forgot: '¿Olvidaste tu contraseña?', loginAction: 'Acceder y Cargar Datos',
                        recoverTitle: 'Recuperar Cuenta', recoverDesc: 'Ingresa tu correo para restablecer tu contraseña.',
                        yourEmail: 'Tu Correo Registrado', sendLink: 'Enviar Enlace', cancel: 'Cancelar y Volver',
                        shippingTitle: 'Datos de Envío',
                        name: 'Nombre Completo', phone: 'Teléfono', address: 'Dirección de Entrega',
                        state: 'Estado / Entidad', // Agregado
                        createAccount: 'Crear una cuenta para futuras compras',
                        confirmPass: 'Confirmar Contraseña',
                        passMismatch: 'Las contraseñas no coinciden',
                        paymentTitle: 'Método de Pago',
                        securePayment: 'Pago 100% Seguro Encriptado por PayPal',
                        yourOrder: 'Tu Pedido',
                        units: 'unidad(es)',
                        subtotal: 'Subtotal', shipping: 'Envío', free: 'Gratis', total: 'Total',
                        processing: 'Procesando tu pedido...', wait: 'No cierres esta ventana',
                        langBtn: 'English',
                        alertEmpty: 'Tu carrito está vacío',
                        alertData: 'Por favor, completa todos los datos obligatorios.',
                        welcomeUser: '¡Bienvenido! Tus datos se han cargado.',
                        couponLabel: 'Código de Descuento', apply: 'Aplicar', discount: 'Descuento', invalidCoupon: 'Cupón no válido'
                    },
                    en: {
                        back: 'Back to store',
                        clientQuestion: 'Already a customer?', 
                        clientDesc: 'Login to load your data automatically.',
                        loginBtn: 'Login',
                        welcome: 'Welcome Back', email: 'Email Address', pass: 'Password',
                        forgot: 'Forgot password?', loginAction: 'Login & Load Data',
                        recoverTitle: 'Recover Account', recoverDesc: 'Enter your email to receive a reset link.',
                        yourEmail: 'Your Registered Email', sendLink: 'Send Link', cancel: 'Cancel',
                        shippingTitle: 'Shipping Details',
                        name: 'Full Name', phone: 'Phone Number', address: 'Shipping Address',
                        state: 'State / Province', // Agregado
                        createAccount: 'Create an account for future purchases',
                        confirmPass: 'Confirm Password',
                        passMismatch: 'Passwords do not match',
                        paymentTitle: 'Payment Method',
                        securePayment: '100% Secure Payment Encrypted by PayPal',
                        yourOrder: 'Your Order',
                        units: 'unit(s)',
                        subtotal: 'Subtotal', shipping: 'Shipping', free: 'Free', total: 'Total',
                        processing: 'Processing your order...', wait: 'Do not close this window',
                        langBtn: 'Español',
                        alertEmpty: 'Your cart is empty',
                        alertData: 'Please complete all required fields.',
                        welcomeUser: 'Welcome! Your data has been loaded.',
                        couponLabel: 'Discount Code', apply: 'Apply', discount: 'Discount', invalidCoupon: 'Invalid coupon'
                    }
                },

                init() {
                    if (this.cart.length === 0) {
                        alert(this.t[this.lang].alertEmpty);
                        window.location.href = 'index.php';
                        return;
                    }
                    const storedUser = localStorage.getItem('cliente_perpetua');
                    if (storedUser) {
                        const u = JSON.parse(storedUser);
                        this.cliente.nombre = u.nombre || '';
                        this.cliente.email = u.email || '';
                        this.cliente.telefono = u.telefono || '';
                        this.cliente.direccion = u.direccion || '';
                        this.userLoggedIn = true;
                    }
                    lucide.createIcons();
                    this.renderPayPal();
                },

                // Nueva función para envío dinámico
                updateShipping() {
                    if(!this.cliente.estado) return;
                    fetch('api/get_shipping.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ estado: this.cliente.estado })
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.shippingCost = parseFloat(data.costo);
                        this.renderPayPal(); 
                    });
                },

                switchLang() { 
                    this.lang = (this.lang === 'es' ? 'en' : 'es'); 
                    localStorage.setItem('lang', this.lang); 
                },

                resetForms() {
                    this.showLoginModal = false;
                    this.loginData = { email: '', password: '' };
                    this.recEmail = '';
                    this.recMsg = '';
                    this.loginError = '';
                    this.loginView = 'login';
                },

                loginCliente() {
                    this.loginError = '';
                    fetch('api/login_checkout.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(this.loginData)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            this.cliente.nombre = data.datos.nombre;
                            this.cliente.telefono = data.datos.telefono;
                            this.cliente.direccion = data.datos.direccion;
                            this.cliente.email = this.loginData.email; 
                            localStorage.setItem('cliente_perpetua', JSON.stringify({
                                nombre: data.datos.nombre,
                                email: this.loginData.email,
                                telefono: data.datos.telefono,
                                direccion: data.datos.direccion
                            }));
                            this.userLoggedIn = true;
                            this.crearCuenta = false;
                            this.resetForms(); 
                            alert(this.t[this.lang].welcomeUser);
                        } else { this.loginError = data.message; }
                    })
                    .catch(err => { this.loginError = "Connection Error"; });
                },

                recoverPassword() {
                    this.recMsg = '';
                    if(!this.recEmail.includes('@')) { this.recMsg = 'Invalid Email'; return; }
                    fetch('api/solicitar_reset.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ email: this.recEmail })
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.recMsg = data.message;
                        if(data.debug_link) alert("DEV LINK: " + data.debug_link);
                    })
                    .catch(err => { this.recMsg = "Error sending request"; });
                },

                applyCoupon() {
                    this.couponMsg = '';
                    if(!this.couponCode) return;
                    fetch('api/validar_cupon.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ codigo: this.couponCode, total: this.getSubtotal() })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.valid) {
                            this.discount = parseFloat(data.data.descuento_aplicado);
                            this.shippingFree = (data.data.tipo_oferta === 'envio' || data.data.tipo_oferta === 'ambos');
                            this.couponApplied = true;
                            this.couponMsg = data.msg;
                        } else {
                            this.discount = 0; this.shippingFree = false; this.couponApplied = false;
                            this.couponMsg = data.msg; 
                        }
                        this.renderPayPal(); 
                    });
                },

                procesarImagen(img) {
                    if (!img) return 'https://via.placeholder.com/150';
                    if (img.includes('http') || img.includes('imgProd/')) return img;
                    return 'admin/imgProd/' + img; // Ruta corregida
                },

                getSubtotal() {
                    return this.cart.reduce((s, i) => s + (i.precio * i.qty), 0);
                },

                // Lógica de suma corregida
                getTotal() {
                    let envioActual = this.shippingFree ? 0 : this.shippingCost;
                    let total = (this.getSubtotal() - this.discount) + envioActual;
                    return total > 0 ? total.toFixed(2) : "0.00";
                },

                formValido() {
                    let basicos = this.cliente.nombre.length > 2 && 
                                  this.cliente.email.includes('@') && 
                                  this.cliente.telefono.length >= 10 && 
                                  this.cliente.direccion.length > 5 &&
                                  this.cliente.estado !== ''; // Estado obligatorio
                    
                    let registro = true;
                    if (!this.userLoggedIn && this.crearCuenta) {
                        registro = this.newAccount.password.length >= 6 && 
                                   (this.newAccount.password === this.newAccount.confirm);
                    }
                    return basicos && registro;
                },

                renderPayPal() {
                    const container = document.getElementById('paypal-button-container');
                    if(container) container.innerHTML = '';
                    paypal.Buttons({
                        style: { layout: 'vertical', color: 'blue', shape: 'pill', label: 'pay' },
                        onClick: (data, actions) => {
                            if (!this.formValido()) {
                                alert(this.t[this.lang].alertData);
                                return actions.reject();
                            }
                        },
                        createOrder: (data, actions) => {
                            return actions.order.create({ purchase_units: [{ amount: { value: this.getTotal() } }] });
                        },
                        onApprove: (data, actions) => {
                            this.isProcessing = true;
                            return actions.order.capture().then((details) => {
                                let payload = {
                                    orderID: data.orderID,
                                    cart: this.cart,
                                    total: this.getTotal(),
                                    cliente: this.cliente,
                                    crear_cuenta: !this.userLoggedIn && this.crearCuenta,
                                    new_password: this.newAccount.password,
                                    cupon: this.couponApplied ? this.couponCode : null
                                };
                                return fetch('api/confirmar_pago.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify(payload)
                                })
                                .then(res => res.json())
                                .then(res => {
                                    if(res.status === 'success') {
                                        localStorage.removeItem('cart'); 
                                        window.location.href = 'gracias.php?id=' + res.pedido_id; 
                                    } else { throw new Error(res.message); }
                                })
                                .catch(err => { alert("Error: " + err.message); this.isProcessing = false; });
                            });
                        }
                    }).render('#paypal-button-container');
                }
            }
        }
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .glass { backdrop-filter: blur(14px); background: rgba(255, 255, 255, 0.8); border: 1px solid rgba(30, 58, 138, 0.1); }
        .dark .glass { background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); }
        [x-cloak] { display: none !important; }
        .btn-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #22d3ee 100%); transition: all 0.3s ease; }
        .btn-gradient:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(34, 211, 238, 0.4); }
    </style>
</head>
<body class="min-h-screen transition-colors duration-500 bg-slate-50 dark:bg-gray-900 text-gray-900 dark:text-white font-sans">

    <div x-show="showLoginModal" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="resetForms()"></div>
        <div x-show="showLoginModal" x-transition class="relative bg-white dark:bg-gray-800 rounded-3xl p-8 max-w-md w-full shadow-2xl border border-gray-100 dark:border-gray-700">
            <button @click="resetForms()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-6 h-6"></i></button>

            <div x-show="loginView === 'login'">
                <h3 class="text-2xl font-black text-center mb-6 uppercase text-perpetua-blue dark:text-perpetua-aqua" x-text="t[lang].welcome"></h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400 ml-2" x-text="t[lang].email"></label>
                        <input type="email" x-model="loginData.email" class="w-full px-5 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-perpetua-aqua">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400 ml-2" x-text="t[lang].pass"></label>
                        <input type="password" x-model="loginData.password" class="w-full px-5 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-perpetua-aqua">
                    </div>
                    <div class="flex justify-end">
                        <button @click="loginView = 'recover'" class="text-xs font-bold text-gray-400 hover:text-perpetua-aqua underline" x-text="t[lang].forgot"></button>
                    </div>
                    <p x-show="loginError" x-text="loginError" class="text-center text-xs text-red-500 font-bold"></p>
                    <button @click="loginCliente()" class="w-full btn-gradient text-white py-3 rounded-xl font-bold uppercase tracking-widest shadow-lg" x-text="t[lang].loginAction"></button>
                </div>
            </div>
            
            <div x-show="loginView === 'recover'">
                <h3 class="text-xl font-black text-center mb-2 uppercase text-gray-700 dark:text-white" x-text="t[lang].recoverTitle"></h3>
                <p class="text-xs text-center text-gray-400 mb-6" x-text="t[lang].recoverDesc"></p>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-bold uppercase text-gray-400 ml-2" x-text="t[lang].yourEmail"></label>
                        <input type="email" x-model="recEmail" class="w-full px-5 py-3 rounded-xl bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-perpetua-aqua">
                    </div>
                    <p x-show="recMsg" x-text="recMsg" class="text-center text-xs font-bold" :class="recMsg.includes('Error') || recMsg.includes('Invalid') ? 'text-red-500' : 'text-green-500'"></p>
                    <button @click="recoverPassword()" class="w-full bg-slate-900 text-white py-3 rounded-xl font-bold uppercase tracking-widest hover:bg-perpetua-aqua transition" x-text="t[lang].sendLink"></button>
                    <button @click="loginView = 'login'" class="w-full text-center text-xs font-bold text-gray-400 hover:text-gray-600 py-2" x-text="t[lang].cancel"></button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-10">
        <div class="flex justify-between items-center mb-10">
            <a href="index.php" class="flex items-center gap-2 text-perpetua-blue dark:text-perpetua-aqua font-bold hover:underline">
                <i data-lucide="arrow-left" class="w-5 h-5"></i> <span x-text="t[lang].back"></span>
            </a>
            
            <div class="flex items-center gap-4">
                <button @click="switchLang()" class="px-3 py-1 rounded-full text-[10px] font-bold border-2 border-perpetua-blue text-perpetua-blue dark:text-perpetua-aqua transition-all">
                    <span x-text="t[lang].langBtn"></span>
                </button>
                <img :src="darkMode ? 'imagenes/designWhite.png' : 'imagenes/Perpetua_Life.png'" class="h-10 w-auto object-contain transition-all duration-500" alt="Logo Perpetualife">
            </div>
        </div>

        <div class="mb-8 rounded-2xl border border-gray-200 bg-white p-4 flex flex-col sm:flex-row justify-between items-center gap-4 shadow-sm" 
             x-show="!userLoggedIn">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-perpetua-blue"><i data-lucide="user" class="w-5 h-5"></i></div>
                <div>
                    <h4 class="font-bold text-gray-900 text-sm" x-text="t[lang].clientQuestion"></h4>
                    <p class="text-xs text-gray-500" x-text="t[lang].clientDesc"></p>
                </div>
            </div>
            <button @click="showLoginModal = true" class="bg-perpetua-blue hover:bg-blue-800 text-white font-bold py-2 px-6 rounded-lg text-xs transition-colors w-full sm:w-auto" x-text="t[lang].loginBtn"></button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-6">
                <div class="glass p-8 rounded-[2rem] shadow-xl border-t-4 border-perpetua-aqua">
                    <h2 class="text-2xl font-black mb-6 uppercase flex items-center gap-3">
                        <i data-lucide="truck" class="text-perpetua-aqua"></i> <span x-text="t[lang].shippingTitle"></span>
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase text-gray-400 ml-2" x-text="t[lang].name"></label>
                            <input type="text" x-model="cliente.nombre" class="w-full px-5 py-4 rounded-2xl bg-white/50 dark:bg-gray-800 border focus:ring-2 focus:ring-perpetua-aqua outline-none transition-all">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase text-gray-400 ml-2" x-text="t[lang].phone"></label>
                            <input type="tel" x-model="cliente.telefono" class="w-full px-5 py-4 rounded-2xl bg-white/50 dark:bg-gray-800 border focus:ring-2 focus:ring-perpetua-aqua outline-none transition-all">
                        </div>
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-xs font-bold uppercase text-gray-400 ml-2" x-text="t[lang].email"></label>
                            <input type="email" x-model="cliente.email" class="w-full px-5 py-4 rounded-2xl bg-white/50 dark:bg-gray-800 border focus:ring-2 focus:ring-perpetua-aqua outline-none transition-all">
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="text-xs font-bold uppercase text-gray-400 ml-2" x-text="t[lang].state"></label>
                            <select x-model="cliente.estado" @change="updateShipping()" class="w-full px-5 py-4 rounded-2xl bg-white/50 dark:bg-gray-800 border border-gray-200 focus:ring-2 focus:ring-perpetua-aqua outline-none cursor-pointer">
                                <option value="" disabled selected>Selecciona tu Estado</option>
                                <template x-for="edo in estadosMx" :key="edo">
                                    <option :value="edo" x-text="edo"></option>
                                </template>
                            </select>
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="text-xs font-bold uppercase text-gray-400 ml-2" x-text="t[lang].address"></label>
                            <textarea x-model="cliente.direccion" rows="3" class="w-full px-5 py-4 rounded-2xl bg-white/50 dark:bg-gray-800 border focus:ring-2 focus:ring-perpetua-aqua outline-none transition-all"></textarea>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-dashed border-gray-300 dark:border-gray-700" 
                         x-show="!userLoggedIn">
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <div class="relative"><input type="checkbox" x-model="crearCuenta" class="sr-only peer"><div class="w-10 h-6 bg-gray-300 rounded-full peer peer-checked:bg-perpetua-aqua transition-all"></div><div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-all peer-checked:translate-x-4"></div></div>
                            <span class="font-bold text-sm" x-text="t[lang].createAccount"></span>
                        </label>
                        <div x-show="crearCuenta" x-transition class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 bg-perpetua-blue/5 p-4 rounded-xl border border-perpetua-blue/10">
                            <div>
                                <label class="text-[10px] font-bold uppercase text-gray-400 ml-1" x-text="t[lang].pass"></label>
                                <input type="password" x-model="newAccount.password" class="w-full px-4 py-2 rounded-lg border focus:border-perpetua-aqua outline-none bg-white dark:bg-gray-800">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold uppercase text-gray-400 ml-1" x-text="t[lang].confirmPass"></label>
                                <input type="password" x-model="newAccount.confirm" class="w-full px-4 py-2 rounded-lg border focus:border-perpetua-aqua outline-none bg-white dark:bg-gray-800">
                            </div>
                            <p class="md:col-span-2 text-xs text-red-500 font-bold" x-show="newAccount.password !== newAccount.confirm && newAccount.confirm.length > 0" x-text="t[lang].passMismatch"></p>
                        </div>
                    </div>
                </div>

                <div class="glass p-8 rounded-[2rem] shadow-xl transition-all" :class="formValido() ? 'opacity-100' : 'opacity-50 grayscale pointer-events-none'">
                    <h2 class="text-xl font-black mb-6 uppercase flex items-center gap-3"><i data-lucide="credit-card" class="text-perpetua-aqua"></i> <span x-text="t[lang].paymentTitle"></span></h2>
                    <div id="paypal-button-container"></div>
                    <p class="text-[10px] text-center mt-4 text-gray-400 font-bold uppercase tracking-widest" x-text="t[lang].securePayment"></p>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="glass p-6 rounded-[2rem] shadow-xl sticky top-24 border-b-4 border-perpetua-blue">
                    <h3 class="font-black uppercase text-sm mb-6 pb-2 border-b dark:border-gray-700" x-text="t[lang].yourOrder"></h3>
                    <div class="space-y-4 max-h-[35vh] overflow-y-auto pr-2 mb-6 custom-scrollbar">
                        <template x-for="item in cart" :key="item.id">
                            <div class="flex items-center gap-3">
                                <img :src="procesarImagen(item.img)" class="w-12 h-12 rounded-xl object-contain bg-white p-1 border">
                                <div class="flex-1">
                                    <p class="text-xs font-bold uppercase truncate w-32" x-text="item.nombre"></p>
                                    <p class="text-[10px] text-gray-400" x-text="item.qty + ' ' + t[lang].units"></p>
                                </div>
                                <span class="font-bold text-perpetua-blue dark:text-perpetua-aqua" x-text="'$' + (item.precio * item.qty).toFixed(2)"></span>
                            </div>
                        </template>
                    </div>

                    <div class="mb-4 pt-4 border-t border-dashed border-gray-300 dark:border-gray-700">
                        <label class="text-[10px] font-bold uppercase text-gray-400 ml-1 mb-1 block" x-text="t[lang].couponLabel"></label>
                        <div class="flex gap-2">
                            <input type="text" x-model="couponCode" placeholder="CODE" class="w-full px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-800 border focus:ring-2 focus:ring-perpetua-aqua outline-none text-sm uppercase font-bold text-gray-700 dark:text-white">
                            <button @click="applyCoupon()" class="bg-gray-900 text-white px-3 py-2 rounded-lg text-xs font-bold uppercase hover:bg-gray-700" x-text="t[lang].apply"></button>
                        </div>
                        <p x-show="couponMsg" x-text="couponMsg" class="text-[10px] font-bold mt-1" :class="couponApplied ? 'text-green-500' : 'text-red-500'"></p>
                    </div>

                    <div class="pt-4 border-t dark:border-gray-700 space-y-2">
                        <div class="flex justify-between text-gray-400 font-bold text-[10px] uppercase">
                            <span x-text="t[lang].subtotal"></span>
                            <span x-text="'$' + getSubtotal().toFixed(2)"></span>
                        </div>
                        
                        <div class="flex justify-between text-perpetua-aqua font-bold text-[10px] uppercase" x-show="discount > 0">
                            <span x-text="t[lang].discount"></span>
                            <span x-text="'-$' + discount.toFixed(2)"></span>
                        </div>

                        <div class="flex justify-between text-gray-400 font-bold text-[10px] uppercase">
                            <span x-text="t[lang].shipping"></span>
                            <template x-if="shippingFree">
                                <span class="text-green-500 font-bold" x-text="t[lang].free"></span>
                            </template>
                            <template x-if="!shippingFree">
                                <span class="text-slate-600 dark:text-slate-300" x-text="'$' + parseFloat(shippingCost).toFixed(2)"></span>
                            </template>
                        </div>

                        <div class="flex justify-between items-end pt-2">
                            <span class="font-black text-lg uppercase tracking-tighter" x-text="t[lang].total"></span>
                            <span class="font-black text-2xl text-perpetua-blue dark:text-perpetua-aqua" x-text="'$' + getTotal()"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="isProcessing" class="fixed inset-0 bg-perpetua-blue/90 backdrop-blur-md z-[100] flex flex-col items-center justify-center text-white" x-cloak>
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-perpetua-aqua mb-4"></div>
        <p class="font-black uppercase tracking-widest animate-pulse" x-text="t[lang].processing"></p>
        <p class="text-sm mt-2 opacity-70" x-text="t[lang].wait"></p>
    </div>

</body>
</html>