<?php
/**
 * completarCompra.php - Perpetualife
 * Flujo de Checkout optimizado
 */
require_once 'api/conexion.php'; 
?>
<!DOCTYPE html>
<html lang="es" x-data="checkoutData()" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra | Perpetualife</title>
    
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
                cart: JSON.parse(localStorage.getItem('cart')) || [],
                cliente: {
                    nombre: '',
                    email: '',
                    direccion: ''
                },
                isProcessing: false,

                init() {
                    // Si el carrito está vacío, regresamos al index
                    if (this.cart.length === 0) {
                        alert("Tu carrito está vacío");
                        window.location.href = 'index.php';
                    }
                    lucide.createIcons();
                    this.renderPayPal();
                },

                totalPrice() {
                    return this.cart.reduce((s, i) => s + (i.precio * i.qty), 0).toFixed(2);
                },

                formValido() {
                    // Validación simple: campos no vacíos y email con formato
                    return this.cliente.nombre.length > 2 && 
                           this.cliente.email.includes('@') && 
                           this.cliente.direccion.length > 5;
                },

                renderPayPal() {
                    paypal.Buttons({
                        style: { layout: 'vertical', color: 'blue', shape: 'pill', label: 'pay' },
                        
                        // Solo permitimos crear la orden si el formulario es válido
                        onClick: (data, actions) => {
                            if (!this.formValido()) {
                                alert("Por favor, completa tus datos de envío correctamente antes de proceder al pago.");
                                return actions.reject();
                            }
                        },

                        createOrder: (data, actions) => {
                            return actions.order.create({
                                purchase_units: [{
                                    amount: { value: this.totalPrice() }
                                }]
                            });
                        },

                        onApprove: (data, actions) => {
                            this.isProcessing = true;
                            return actions.order.capture().then((details) => {
                                return fetch('api/confirmar_pago.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({
                                        orderID: data.orderID,
                                        cart: this.cart,
                                        total: this.totalPrice(),
                                        cliente: this.cliente
                                    })
                                })
                                .then(res => res.json())
                                .then(res => {
                                    if(res.status === 'success') {
                                        localStorage.removeItem('cart'); // Limpiamos el carrito local
                                        // REDIRECCIÓN A LA PÁGINA DE GRACIAS PASANDO EL ID DEL PEDIDO
                                        window.location.href = 'gracias.php?id=' + res.pedido_id; 
                                    } else {
                                        alert("Error: " + res.message);
                                        this.isProcessing = false;
                                    }
                                });
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
    </style>
</head>
<body class="min-h-screen transition-colors duration-500 bg-slate-50 dark:bg-gray-900 text-gray-900 dark:text-white font-sans">

    <div class="max-w-6xl mx-auto px-4 py-10">
        <div class="flex justify-between items-center mb-10">
            <a href="index.php" class="flex items-center gap-2 text-perpetua-blue dark:text-perpetua-aqua font-bold">
                <i data-lucide="arrow-left" class="w-5 h-5"></i> Volver a la tienda
            </a>
            <img :src="darkMode ? 'imagenes/designWhite.png' : 'imagenes/Perpetua_Life.png'" 
     class="h-10 w-auto object-contain transition-all duration-500">
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="glass p-8 rounded-[2rem] shadow-xl border-t-4 border-perpetua-aqua">
                    <h2 class="text-2xl font-black mb-6 uppercase flex items-center gap-3">
                        <i data-lucide="truck" class="text-perpetua-aqua"></i> Datos de Envío
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase text-gray-400 ml-2">Nombre Completo</label>
                            <input type="text" x-model="cliente.nombre" placeholder="Ej. Gustavo Cruz" 
                                   class="w-full px-5 py-4 rounded-2xl bg-white/50 dark:bg-gray-800 border focus:ring-2 focus:ring-perpetua-aqua outline-none transition-all">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase text-gray-400 ml-2">Correo Electrónico</label>
                            <input type="email" x-model="cliente.email" placeholder="ejemplo@correo.com" 
                                   class="w-full px-5 py-4 rounded-2xl bg-white/50 dark:bg-gray-800 border focus:ring-2 focus:ring-perpetua-aqua outline-none transition-all">
                        </div>
                        <div class="md:col-span-2 space-y-2">
                            <label class="text-xs font-bold uppercase text-gray-400 ml-2">Dirección de Entrega</label>
                            <textarea x-model="cliente.direccion" placeholder="Calle, número, colonia y ciudad..." rows="3"
                                      class="w-full px-5 py-4 rounded-2xl bg-white/50 dark:bg-gray-800 border focus:ring-2 focus:ring-perpetua-aqua outline-none transition-all"></textarea>
                        </div>
                    </div>
                </div>

                <div class="glass p-8 rounded-[2rem] shadow-xl transition-all" 
                     :class="formValido() ? 'opacity-100' : 'opacity-40 grayscale pointer-events-none'">
                    <h2 class="text-xl font-black mb-6 uppercase flex items-center gap-3">
                        <i data-lucide="credit-card" class="text-perpetua-aqua"></i> Método de Pago
                    </h2>
                    <div id="paypal-button-container"></div>
                    <p class="text-[10px] text-center mt-4 text-gray-400 font-bold uppercase tracking-widest">
                        Pago 100% Seguro Encriptado por PayPal
                    </p>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div class="glass p-6 rounded-[2rem] shadow-xl sticky top-24 border-b-4 border-perpetua-blue">
                    <h3 class="font-black uppercase text-sm mb-6 pb-2 border-b dark:border-gray-700">Tu Pedido</h3>
                    
                    <div class="space-y-4 max-h-[40vh] overflow-y-auto pr-2 mb-6">
                        <template x-for="item in cart" :key="item.id">
                            <div class="flex items-center gap-3">
                                <img :src="item.img" class="w-12 h-12 rounded-xl object-contain bg-white p-1 border">
                                <div class="flex-1">
                                    <p class="text-xs font-bold uppercase truncate w-32" x-text="item.nombre"></p>
                                    <p class="text-[10px] text-gray-400" x-text="item.qty + ' unidad(es)'"></p>
                                </div>
                                <span class="font-bold text-perpetua-blue dark:text-perpetua-aqua" 
                                      x-text="'$' + (item.precio * item.qty).toFixed(2)"></span>
                            </div>
                        </template>
                    </div>

                    <div class="pt-4 border-t dark:border-gray-700 space-y-2">
                        <div class="flex justify-between text-gray-400 font-bold text-[10px] uppercase">
                            <span>Subtotal</span>
                            <span x-text="'$' + totalPrice()"></span>
                        </div>
                        <div class="flex justify-between text-gray-400 font-bold text-[10px] uppercase">
                            <span>Envío</span>
                            <span class="text-green-500">Gratis</span>
                        </div>
                        <div class="flex justify-between items-end pt-2">
                            <span class="font-black text-lg uppercase tracking-tighter">Total</span>
                            <span class="font-black text-2xl text-perpetua-blue dark:text-perpetua-aqua" x-text="'$' + totalPrice()"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="isProcessing" class="fixed inset-0 bg-perpetua-blue/90 backdrop-blur-md z-[100] flex flex-col items-center justify-center text-white" x-cloak>
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-perpetua-aqua mb-4"></div>
        <p class="font-black uppercase tracking-widest animate-pulse">Procesando tu pedido...</p>
    </div>

</body>
</html>