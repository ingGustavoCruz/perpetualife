<?php require_once 'api/conexion.php'; ?>
<!DOCTYPE html>
<html lang="es" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Términos y Condiciones | Perpetualife</title>
    <link rel="icon" type="image/png" href="imagenes/monito01.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { 'perpetua-blue': '#1e3a8a', 'perpetua-aqua': '#22d3ee' } } } }
    </script>
</head>
<body class="min-h-screen bg-slate-50 dark:bg-gray-900 text-gray-900 dark:text-white font-sans transition-colors duration-500">
    <div class="max-w-4xl mx-auto px-6 py-12">
        
        <div class="flex justify-between items-center mb-12">
            <a href="index.php" class="flex items-center gap-2 text-perpetua-blue dark:text-perpetua-aqua font-bold hover:underline">
                <i data-lucide="arrow-left" class="w-5 h-5"></i> Volver a la tienda
            </a>
            <img :src="darkMode ? 'imagenes/designWhite.png' : 'imagenes/Perpetua_Life.png'" class="h-8 object-contain">
        </div>

        <div class="bg-white dark:bg-gray-800 p-10 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700">
            <h1 class="text-3xl font-black text-perpetua-blue dark:text-perpetua-aqua mb-6 uppercase">Términos y Condiciones</h1>
            
            <div class="prose dark:prose-invert max-w-none text-gray-500 dark:text-gray-400">
                <p class="mb-4">Última actualización: <?php echo date('d/m/Y'); ?></p>
                
                <hr class="border-gray-200 dark:border-gray-700 my-8">
                
                <p class="italic">
                    [Aquí se redactarán los términos legales, condiciones de uso, políticas de envío y devoluciones. 
                    Este documento es necesario para cumplir con los requisitos de las pasarelas de pago.]
                </p>
                
                <div class="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-900/50 rounded-xl mt-8 border-2 border-dashed border-gray-200 dark:border-gray-700">
                    <span class="text-gray-300 font-bold uppercase tracking-widest">Contenido en Construcción</span>
                </div>
            </div>
        </div>

    </div>
    <script>lucide.createIcons();</script>
</body>
</html>