<?php
/**
 * gracias.php - Perpetualife
 * Página de éxito Premium: Mejoras de UX, Social Proof y Feedback de Envío
 */
require_once 'api/conexion.php';

$id_pedido = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id_pedido) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" x-data="thankYouData()" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Gracias por tu compra! | Perpetualife</title>
    
    <link rel="icon" type="image/png" href="imagenes/monito01.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
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

        function thankYouData() {
            return {
                darkMode: localStorage.getItem('darkMode') === 'true',
                lang: localStorage.getItem('lang') || 'es',
                estrellas: 0,
                hoverEstrellas: 0,
                enviado: false,
                cargando: false, // Variable para controlar el estado del botón
                comentario: '',

                t: {
                    es: {
                        title: '¡Pago Exitoso!',
                        desc: 'Tu pedido ha sido procesado correctamente y ya estamos preparando tus productos.',
                        orderLabel: 'Número de Pedido',
                        back: 'Volver a la tienda',
                        footer: 'Recibirás un correo con los detalles de tu envío.',
                        langBtn: 'English',
                        reviewTitle: '¡Tu opinión vale oro!',
                        reviewSubtitle: '¿Cómo fue tu experiencia de compra?',
                        verified: 'Comprador Verificado',
                        reviewPlaceholder: 'Escribe un breve comentario (opcional)',
                        reviewBtn: 'Enviar Reseña',
                        sending: 'Enviando...',
                        reviewThanks: '¡Muchas gracias!',
                        reviewPending: 'Tu reseña será revisada por nuestro equipo.'
                    },
                    en: {
                        title: 'Payment Successful!',
                        desc: 'Your order has been processed successfully and we are preparing your products.',
                        orderLabel: 'Order Number',
                        back: 'Back to store',
                        footer: 'You will receive an email with your shipping details.',
                        langBtn: 'Español',
                        reviewTitle: 'Your opinion matters!',
                        reviewSubtitle: 'How was your shopping experience?',
                        verified: 'Verified Buyer',
                        reviewPlaceholder: 'Write a short comment (optional)',
                        reviewBtn: 'Submit Review',
                        sending: 'Sending...',
                        reviewThanks: 'Thank you very much!',
                        reviewPending: 'Your review will be reviewed by our team.'
                    }
                },

                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                    localStorage.setItem('darkMode', this.darkMode);
                },

                switchLang() {
                    this.lang = (this.lang === 'es' ? 'en' : 'es');
                    localStorage.setItem('lang', this.lang);
                },

                enviarResena() {
                    if (this.estrellas === 0 || this.cargando) return; // Evitar doble envío
                    
                    this.cargando = true; // Activar estado de carga
                    const pedidoId = "<?php echo $id_pedido; ?>";
                    
                    fetch('api/guardar_resena.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            pedido_id: pedidoId,
                            estrellas: this.estrellas,
                            comentario: this.comentario
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') {
                            this.enviado = true;
                            setTimeout(() => lucide.createIcons(), 100);
                        }
                    })
                    .catch(err => console.error("Error:", err))
                    .finally(() => {
                        this.cargando = false; // Desactivar carga al finalizar
                    });
                }
            }
        }
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        .glass { backdrop-filter: blur(14px); background: rgba(255, 255, 255, 0.8); border: 1px solid rgba(30, 58, 138, 0.1); }
        .dark .glass { background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); }
        .btn-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #22d3ee 100%); transition: all 0.3s ease; }
        .btn-gradient:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(34, 211, 238, 0.4); }
        [x-cloak] { display: none !important; }
        .star-anim { transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    </style>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-gray-900 flex flex-col items-center justify-center p-4 transition-colors duration-500 relative">

    <div class="absolute top-6 right-6 flex items-center gap-3">
        <button @click="switchLang()" class="px-3 py-1 rounded-full text-[10px] font-bold border-2 border-perpetua-blue text-perpetua-blue dark:text-perpetua-aqua transition-all hover:bg-perpetua-blue hover:text-white dark:hover:bg-perpetua-aqua dark:hover:text-gray-900">
            <span x-text="t[lang].langBtn"></span>
        </button>
        <button @click="toggleDarkMode()" class="p-2 rounded-full hover:bg-blue-100 dark:hover:bg-blue-900/40 text-perpetua-blue dark:text-perpetua-aqua">
            <i data-lucide="sun" x-show="darkMode"></i>
            <i data-lucide="moon" x-show="!darkMode"></i>
        </button>
    </div>

    <div class="max-w-md w-full glass p-10 rounded-[3rem] shadow-2xl text-center border-t-[8px] border-perpetua-aqua mb-6">
        <div class="mb-6 flex justify-center">
            <div class="w-24 h-24 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-500 shadow-inner">
                <i data-lucide="party-popper" class="w-12 h-12 animate-bounce"></i>
            </div>
        </div>
        <h1 class="text-3xl font-black text-perpetua-blue dark:text-perpetua-aqua mb-3 uppercase tracking-tight" x-text="t[lang].title"></h1>
        <p class="text-sm text-gray-500 dark:text-gray-300 font-medium mb-8 leading-relaxed" x-text="t[lang].desc"></p>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-6 mb-8 border border-gray-100 dark:border-gray-700">
            <span class="text-[10px] font-bold text-gray-400 uppercase block mb-2 tracking-widest" x-text="t[lang].orderLabel"></span>
            <span class="text-2xl font-black text-gray-800 dark:text-white select-all">#PERP-<?php echo str_pad($id_pedido, 5, "0", STR_PAD_LEFT); ?></span>
        </div>
        <div class="space-y-6">
            <a href="index.php" class="block w-full btn-gradient text-white py-4 rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg" x-text="t[lang].back"></a>
            <p class="text-[10px] text-gray-400 font-bold uppercase" x-text="t[lang].footer"></p>
        </div>
    </div>

    <div class="glass p-8 rounded-[3rem] shadow-xl max-w-md w-full border-t-[8px] border-yellow-400 text-center">
        <div x-show="!enviado" x-cloak>
            <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase mb-1" x-text="t[lang].reviewTitle"></h3>
            <div class="flex items-center justify-center gap-1 mb-4">
                <i data-lucide="shield-check" class="w-3 h-3 text-emerald-500"></i>
                <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-tighter" x-text="t[lang].verified"></span>
            </div>
            <p class="text-xs text-slate-500 dark:text-gray-400 mb-6" x-text="t[lang].reviewSubtitle"></p>
            
            <div class="flex gap-2 mb-6 justify-center" @mouseleave="hoverEstrellas = 0">
                <template x-for="i in 5">
                    <button @click="estrellas = i" @mouseenter="hoverEstrellas = i" class="star-anim focus:outline-none">
                        <i data-lucide="star" :class="(i <= (hoverEstrellas || estrellas)) ? 'fill-yellow-400 text-yellow-400' : 'text-slate-300 dark:text-gray-600'" class="w-8 h-8 transition-colors duration-200"></i>
                    </button>
                </template>
            </div>

            <textarea x-model="comentario" :placeholder="t[lang].reviewPlaceholder" class="w-full p-4 rounded-2xl bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 outline-none focus:ring-2 focus:ring-yellow-400 mb-4 h-24 text-sm dark:text-white transition-all"></textarea>
            
            <button @click="enviarResena()" 
                    :disabled="estrellas === 0 || cargando" 
                    :class="estrellas > 0 && !cargando ? 'bg-perpetua-blue dark:bg-perpetua-aqua text-white dark:text-gray-900 shadow-lg' : 'bg-slate-300 dark:bg-gray-700 text-slate-500 cursor-not-allowed'"
                    class="w-full py-4 rounded-2xl font-bold uppercase tracking-widest transition-all duration-300 text-xs flex items-center justify-center gap-2">
                
                <template x-if="!cargando">
                    <span x-text="t[lang].reviewBtn"></span>
                </template>
                
                <template x-if="cargando">
                    <div class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-current" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="t[lang].sending"></span>
                    </div>
                </template>
            </button>
        </div>
        
        <div x-show="enviado" x-cloak x-transition class="py-6">
            <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center text-emerald-500 mx-auto mb-4">
                <i data-lucide="check-circle" class="w-10 h-10"></i>
            </div>
            <h4 class="font-black text-slate-800 dark:text-white uppercase" x-text="t[lang].reviewThanks"></h4>
            <p class="text-xs text-slate-500 dark:text-gray-400 mt-2" x-text="t[lang].reviewPending"></p>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => lucide.createIcons());
    </script>
</body>
</html>