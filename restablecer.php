<?php
/**
 * restablecer.php
 * Formulario para ingresar la nueva contraseña (Bilingüe + Dark Mode)
 */
require_once 'api/conexion.php';

$token = isset($_GET['token']) ? $conn->real_escape_string($_GET['token']) : '';
$valido = false;
$mensaje_php = '';

// 1. Verificar si el token es válido y no ha expirado
if ($token) {
    $sql = "SELECT id FROM kaiexper_perpetualife.clientes WHERE token_recuperacion = '$token' AND token_expiracion > NOW()";
    $res = $conn->query($sql);
    if ($res->num_rows > 0) {
        $valido = true;
    } else {
        // Mensaje genérico, el texto real lo manejará el JS según el idioma, 
        // pero dejamos esto por si falla JS.
        $mensaje_php = "Link expired or invalid."; 
    }
}

// 2. Procesar el cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valido) {
    $pass = $_POST['password'];
    $confirm = $_POST['confirm'];
    
    if ($pass === $confirm && strlen($pass) >= 6) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        // Actualizamos password y borramos el token
        $conn->query("UPDATE kaiexper_perpetualife.clientes SET password = '$hash', token_recuperacion = NULL, token_expiracion = NULL WHERE token_recuperacion = '$token'");
        
        header("Location: index.php?msg=password_reset");
        exit;
    } else {
        $error_php = "Error: Passwords do not match or are too short.";
    }
}
?>
<!DOCTYPE html>
<html lang="es" x-data="resetData()" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña | Perpetualife</title>
    <link rel="icon" type="image/png" href="imagenes/KAI_NG.png">
    
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

        function resetData() {
            return {
                darkMode: localStorage.getItem('darkMode') === 'true',
                lang: localStorage.getItem('lang') || 'es',
                
                // Variables del formulario para validación visual
                pass: '',
                confirm: '',

                t: {
                    es: {
                        title: 'Nueva Contraseña',
                        lblPass: 'Nueva Contraseña',
                        lblConfirm: 'Confirmar Contraseña',
                        btnSave: 'Guardar Contraseña',
                        invalidToken: 'El enlace ha caducado o no es válido.',
                        back: 'Volver al inicio',
                        langBtn: 'English',
                        matchError: 'Las contraseñas no coinciden',
                        shortError: 'Mínimo 6 caracteres'
                    },
                    en: {
                        title: 'New Password',
                        lblPass: 'New Password',
                        lblConfirm: 'Confirm Password',
                        btnSave: 'Save Password',
                        invalidToken: 'The link has expired or is invalid.',
                        back: 'Back to home',
                        langBtn: 'Español',
                        matchError: 'Passwords do not match',
                        shortError: 'Minimum 6 characters'
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

                isValid() {
                    return this.pass.length >= 6 && this.pass === this.confirm;
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
<body class="min-h-screen bg-slate-50 dark:bg-gray-900 flex items-center justify-center transition-colors duration-500 relative p-4">

    <div class="absolute top-6 right-6 flex items-center gap-3">
        <button @click="switchLang()" class="px-3 py-1 rounded-full text-[10px] font-bold border-2 border-perpetua-blue text-perpetua-blue dark:text-perpetua-aqua transition-all">
            <span x-text="t[lang].langBtn"></span>
        </button>
        <button @click="toggleDarkMode()" class="p-2 rounded-full hover:bg-blue-100 dark:hover:bg-blue-900/40 text-perpetua-blue dark:text-perpetua-aqua">
            <i data-lucide="sun" x-show="darkMode"></i>
            <i data-lucide="moon" x-show="!darkMode"></i>
        </button>
    </div>

    <div class="bg-white dark:bg-gray-800 p-8 rounded-[2rem] shadow-2xl max-w-md w-full border border-slate-100 dark:border-gray-700 transition-colors">
        
        <h2 class="text-2xl font-black text-slate-800 dark:text-white mb-6 text-center uppercase" x-text="t[lang].title"></h2>
        
        <?php if ($valido): ?>
            <form method="POST" class="space-y-6">
                
                <?php if (isset($error_php)): ?>
                    <p class="text-red-500 text-sm font-bold text-center"><?php echo $error_php; ?></p>
                <?php endif; ?>
                
                <div>
                    <label class="text-xs font-bold uppercase text-gray-400 ml-2" x-text="t[lang].lblPass"></label>
                    <input type="password" name="password" x-model="pass" required 
                           class="w-full px-5 py-3 rounded-xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 outline-none focus:ring-2 focus:ring-perpetua-aqua text-gray-800 dark:text-white transition-all">
                    
                    <p x-show="pass.length > 0 && pass.length < 6" class="text-xs text-red-500 mt-1 ml-2 font-bold" x-text="t[lang].shortError"></p>
                </div>
                
                <div>
                    <label class="text-xs font-bold uppercase text-gray-400 ml-2" x-text="t[lang].lblConfirm"></label>
                    <input type="password" name="confirm" x-model="confirm" required 
                           class="w-full px-5 py-3 rounded-xl bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 outline-none focus:ring-2 focus:ring-perpetua-aqua text-gray-800 dark:text-white transition-all">
                    
                    <p x-show="confirm.length > 0 && pass !== confirm" class="text-xs text-red-500 mt-1 ml-2 font-bold" x-text="t[lang].matchError"></p>
                </div>

                <button type="submit" 
                        class="w-full btn-gradient text-white py-3 rounded-xl font-bold uppercase tracking-widest shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="!isValid()"
                        x-text="t[lang].btnSave">
                </button>
            </form>
        <?php else: ?>
            <div class="text-center py-8">
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 text-red-500">
                    <i data-lucide="alert-circle" class="w-8 h-8"></i>
                </div>
                <p class="text-red-500 font-bold mb-6" x-text="t[lang].invalidToken"></p>
                <a href="index.php" class="text-perpetua-aqua font-bold hover:underline uppercase text-sm tracking-widest" x-text="t[lang].back"></a>
            </div>
        <?php endif; ?>
    </div>

    <script>window.addEventListener('load', () => lucide.createIcons());</script>
</body>
</html>