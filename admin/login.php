<?php
/**
 * admin/login.php
 * Versión Final: Seguridad Avanzada + Logs + Regeneración de ID de Sesión
 */
session_start();

// 1. Si ya hay sesión válida, mandar al dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// 2. Conexión y Logger
require_once '../api/conexion.php';
require_once '../api/logger.php'; // Para registrar accesos

$error = '';

// 3. Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitización básica (trim) antes de la lógica
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    // Consulta Segura (Prepared Statement)
    // Buscamos explícitamente en la BD correcta
    $stmt = $conn->prepare("SELECT id, nombre, password, rol FROM kaiexper_perpetualife.admin_usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user_data = $result->fetch_assoc()) {
        // Verificar contraseña
        if (password_verify($pass, $user_data['password'])) {
            
            // --- ÉXITO ---
            
            // A) Prevención de Session Fixation (CRÍTICO)
            session_regenerate_id(true);

            // B) Establecer variables de sesión
            $_SESSION['admin_id'] = $user_data['id'];
            $_SESSION['admin_nombre'] = $user_data['nombre'];
            $_SESSION['admin_rol'] = $user_data['rol'];
            
            // C) Registrar LOG de Acceso Exitoso
            // Nota: Aquí forzamos las variables de sesión temporalmente para el log, 
            // ya que logger.php las lee de $_SESSION
            registrarBitacora('Seguridad', 'Login', "Acceso exitoso: {$user_data['nombre']} (IP: {$_SERVER['REMOTE_ADDR']})");
            
            // D) Redirigir
            header("Location: index.php");
            exit;

        } else {
            // --- FALLO (Contraseña incorrecta) ---
            $error = "La contraseña es incorrecta.";
            
            // Log de Intento Fallido (Seguridad)
            // Usamos un logger manual aquí porque no hay sesión
            registrarBitacora('Seguridad', 'Fallo Login', "Intento fallido (Pass Incorrecto) para: $email (IP: {$_SERVER['REMOTE_ADDR']})");
        }
    } else {
        // --- FALLO (Usuario no existe) ---
        $error = "El correo no existe o no tiene acceso.";
        registrarBitacora('Seguridad', 'Fallo Login', "Intento fallido (Usuario No Existe): $email (IP: {$_SERVER['REMOTE_ADDR']})");
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Perpetualife Admin</title>
    <link rel="icon" type="image/png" href="../imagenes/monito01.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-lg w-full bg-white rounded-[2.5rem] shadow-2xl p-10 relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-cyan-400 to-blue-600"></div>

        <div class="text-center mb-8">
            <img src="../imagenes/Perpetua_Life.png" alt="Perpetualife Logo" class="hidden md:block w-96 mx-auto mb-6 object-contain">
            
            <img src="../imagenes/logoPerpetua.png" alt="Perpetualife Logo" class="block md:hidden w-32 mx-auto mb-4 object-contain">
            
            <h1 class="text-2xl font-black text-slate-900 uppercase tracking-tighter">Panel de Control</h1>
            <p class="text-slate-400 text-sm font-bold">ACCESO RESTRINGIDO</p>
        </div>

        <?php if(!empty($error)): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-xl flex items-center gap-3 animate-pulse">
            <i data-lucide="alert-circle" class="text-red-500 w-5 h-5"></i>
            <p class="text-red-700 text-xs font-bold uppercase"><?php echo $error; ?></p>
        </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-5">
            <div>
                <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Correo Electrónico</label>
                <div class="relative">
                    <i data-lucide="mail" class="absolute left-4 top-4 w-5 h-5 text-slate-300"></i>
                    <input type="email" name="email" required placeholder="admin@perpetualife.com"
                           class="w-full pl-12 pr-5 py-4 rounded-2xl bg-slate-50 border border-slate-200 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 outline-none transition-all font-bold text-slate-700">
                </div>
            </div>
            <div>
                <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Contraseña</label>
                <div class="relative">
                    <i data-lucide="lock" class="absolute left-4 top-4 w-5 h-5 text-slate-300"></i>
                    <input type="password" name="password" required placeholder="••••••••"
                           class="w-full pl-12 pr-5 py-4 rounded-2xl bg-slate-50 border border-slate-200 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 outline-none transition-all font-bold text-slate-700">
                </div>
            </div>
            
            <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black uppercase tracking-widest hover:bg-cyan-600 transition-all shadow-lg hover:shadow-cyan-500/30 flex items-center justify-center gap-2 mt-4">
                Entrar al Sistema <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </form>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>