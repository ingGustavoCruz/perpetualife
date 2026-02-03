<?php
require_once 'api/conexion.php';

$id_pedido = isset($_GET['id']) ? $_GET['id'] : null;

// Si no hay ID de pedido, redirigir al index
if (!$id_pedido) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Gracias por tu compra! | Perpetualife</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .glass { backdrop-filter: blur(14px); background: rgba(255, 255, 255, 0.8); border: 1px solid rgba(30, 58, 138, 0.1); }
        .dark .glass { background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); }
        .btn-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #22d3ee 100%); }
    </style>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-gray-900 flex items-center justify-center p-4 transition-colors duration-500">

    <div class="max-w-md w-full glass p-10 rounded-[3rem] shadow-2xl text-center border-t-8 border-perpetua-aqua">
        <div class="mb-6 flex justify-center">
            <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-green-500">
                <i data-lucide="party-popper" class="w-10 h-10 animate-bounce"></i>
            </div>
        </div>

        <h1 class="text-3xl font-black text-perpetua-blue dark:text-perpetua-aqua mb-2 uppercase tracking-tight">¡Pago Exitoso!</h1>
        <p class="text-gray-500 dark:text-gray-400 font-medium mb-8">Tu pedido ha sido procesado correctamente y ya estamos preparando tus productos.</p>

        <div class="bg-gray-100 dark:bg-gray-800 rounded-2xl p-4 mb-8">
            <span class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Número de Pedido</span>
            <span class="text-xl font-black text-gray-800 dark:text-white">#PERP-<?php echo str_pad($id_pedido, 5, "0", STR_PAD_LEFT); ?></span>
        </div>

        <div class="space-y-4">
            <a href="index.php" class="block w-full btn-gradient text-white py-4 rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg hover:scale-105 transition-transform">
                Volver a la tienda
            </a>
            <p class="text-[9px] text-gray-400 font-bold uppercase">Recibirás un correo con los detalles de tu envío.</p>
        </div>
    </div>

    <script>window.addEventListener('load', () => lucide.createIcons());</script>
</body>
</html>