<?php
/**
 * gracias.php - Perpetualife
 * Página de éxito bilingüe
 */
require_once 'api/conexion.php';

$id_pedido = isset($_GET['id']) ? $_GET['id'] : null;

// Si no hay ID de pedido, redirigir al index
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
    <title>¡Gracias! | Perpetualife</title>
    
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
                
                t: {
                    es: {
                        title: '¡Pago Exitoso!',
                        desc: 'Tu pedido ha sido procesado correctamente y ya estamos preparando tus productos.',
                        orderLabel: 'Número de Pedido',
                        back: 'Volver a la tienda',
                        footer: 'Recibirás un correo con los detalles de tu envío.',
                        langBtn: 'English'
                    },
                    en: {
                        title: 'Payment Successful!',
                        desc: 'Your order has been processed successfully and we are preparing your products.',
                        orderLabel: 'Order Number',
                        back: 'Back to store',
                        footer: 'You will receive an email with your shipping details.',
                        langBtn: 'Español'
                    }
                },

                toggleDarkMode() {
                    this.darkMode = !this.darkMode;
                    localStorage.setItem('darkMode', this.darkMode);
                },

                switchLang() {
                    this.lang = (this.lang === 'es' ? 'en' : 'es');
                    localStorage.setItem('lang', this.lang);
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
    </style>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-gray-900 flex items-center justify-center p-4 transition-colors duration-500 relative">

    <div class="absolute top-6 right-6 flex items-center gap-3">
        <button @click="switchLang()" class="px-3 py-1 rounded-full text-[10px] font-bold border-2 border-perpetua-blue text-perpetua-blue dark:text-perpetua-aqua transition-all">
            <span x-text="t[lang].langBtn"></span>
        </button>
        <button @click="toggleDarkMode()" class="p-2 rounded-full hover:bg-blue-100 dark:hover:bg-blue-900/40 text-perpetua-blue dark:text-perpetua-aqua">
            <i data-lucide="sun" x-show="darkMode"></i>
            <i data-lucide="moon" x-show="!darkMode"></i>
        </button>
    </div>

    <div class="max-w-md w-full glass p-10 rounded-[3rem] shadow-2xl text-center border-t-8 border-perpetua-aqua">
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
            <a href="index.php" class="block w-full btn-gradient text-white py-4 rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg hover:shadow-perpetua-aqua/50" x-text="t[lang].back">
            </a>
            <p class="text-[10px] text-gray-400 font-bold uppercase" x-text="t[lang].footer"></p>
        </div>
    </div>

    <script>window.addEventListener('load', () => lucide.createIcons());</script>
</body>
</html>