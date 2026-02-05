<?php
/**
 * admin/login.php
 * Versión Final: Formulario + Lógica de Validación + Logo Responsive (Doble Tamaño)
 */
session_start();

// 1. Si ya hay sesión, mandar al dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// 2. Conexión a BD
require_once '../api/conexion.php';

$error = '';

// 3. Procesar el formulario cuando se envía (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['usuario'];
    $pass = $_POST['password'];

    // Consulta a la BD
    $stmt = $conn->prepare("SELECT id, password, nombre FROM kaiexper_perpetualife.usuarios_admin WHERE usuario = ? LIMIT 1");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user_data = $result->fetch_assoc()) {
        // Verificar contraseña encriptada
        if (password_verify($pass, $user_data['password'])) {
            // Login Exitoso
            $_SESSION['admin_id'] = $user_data['id'];
            $_SESSION['admin_nombre'] = $user_data['nombre'];
            
            // Actualizar fecha de último login
            $conn->query("UPDATE kaiexper_perpetualife.usuarios_admin SET ultimo_login = NOW() WHERE id = " . $user_data['id']);
            
            header("Location: index.php");
            exit;
        } else {
            $error = "La contraseña es incorrecta.";
        }
    } else {
        $error = "El usuario no existe.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
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
                <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Usuario</label>
                <div class="relative">
                    <i data-lucide="user" class="absolute left-4 top-4 w-5 h-5 text-slate-300"></i>
                    <input type="text" name="usuario" required 
                           class="w-full pl-12 pr-5 py-4 rounded-2xl bg-slate-50 border border-slate-200 focus:border-cyan-400 focus:ring-2 focus:ring-cyan-100 outline-none transition-all font-bold text-slate-700">
                </div>
            </div>
            <div>
                <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Contraseña</label>
                <div class="relative">
                    <i data-lucide="lock" class="absolute left-4 top-4 w-5 h-5 text-slate-300"></i>
                    <input type="password" name="password" required 
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