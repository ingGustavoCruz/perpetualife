<?php
session_start();
if(isset($_SESSION['admin_id'])) { header("Location: index.php"); exit; }

// Capturamos el error si existe en la URL
$error = isset($_GET['error']) ? $_GET['error'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login | Perpetualife Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-[2.5rem] shadow-2xl p-10">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter">Panel de Control</h1>
            <p class="text-slate-400 text-sm font-bold">ACCESO RESTRINGIDO</p>
        </div>

        <?php if($error == 1): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-xl flex items-center gap-3 animate-bounce">
            <i data-lucide="alert-circle" class="text-red-500 w-5 h-5"></i>
            <p class="text-red-700 text-xs font-bold uppercase">Usuario o contraseña incorrectos</p>
        </div>
        <?php endif; ?>

        <form action="auth.php" method="POST" class="space-y-4">
            <div>
                <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Usuario</label>
                <input type="text" name="usuario" required 
                       class="w-full px-5 py-4 rounded-2xl bg-slate-50 border focus:ring-2 focus:ring-cyan-400 outline-none transition-all">
            </div>
            <div>
                <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Contraseña</label>
                <input type="password" name="password" required 
                       class="w-full px-5 py-4 rounded-2xl bg-slate-50 border focus:ring-2 focus:ring-cyan-400 outline-none transition-all">
            </div>
            <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black uppercase tracking-widest hover:bg-cyan-500 transition-all shadow-lg flex items-center justify-center gap-2">
                <i data-lucide="log-in" class="w-4 h-4"></i> Entrar al Sistema
            </button>
        </form>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>