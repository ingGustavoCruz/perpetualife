<?php
/**
 * perfil.php - Área de Cliente (Historial y Datos)
 */
require_once 'api/conexion.php';
session_start();

// Si no hay sesión, mandar al home
if (!isset($_SESSION['cliente_id'])) {
    header("Location: index.php");
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

// 1. OBTENER DATOS DEL CLIENTE
$stmt = $conn->prepare("SELECT * FROM kaiexper_perpetualife.clientes WHERE id = ?");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();

// 2. OBTENER PEDIDOS
$pedidos = [];
$q_pedidos = $conn->query("SELECT * FROM kaiexper_perpetualife.pedidos WHERE cliente_id = $cliente_id ORDER BY fecha DESC");
if ($q_pedidos) {
    while($p = $q_pedidos->fetch_assoc()) {
        $pedidos[] = $p;
    }
}

// 3. ACTUALIZAR DATOS (Si envía el formulario)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $tel = $_POST['telefono'];
    $dir = $_POST['direccion'];
    
    $upd = $conn->prepare("UPDATE kaiexper_perpetualife.clientes SET nombre=?, telefono=?, direccion=? WHERE id=?");
    $upd->bind_param("sssi", $nombre, $tel, $dir, $cliente_id);
    if ($upd->execute()) {
        $msg = 'ok';
        // Refrescar datos
        $cliente['nombre'] = $nombre;
        $cliente['telefono'] = $tel;
        $cliente['direccion'] = $dir;
        $_SESSION['cliente_nombre'] = $nombre;
    }
}
?>
<!DOCTYPE html>
<html lang="es" x-data="profileData()" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | Perpetualife</title>
    <link rel="icon" type="image/png" href="imagenes/KAI_NG.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: { 'perpetua-blue': '#1e3a8a', 'perpetua-aqua': '#22d3ee' }
                }
            }
        }

        function profileData() {
            return {
                darkMode: localStorage.getItem('darkMode') === 'true',
                lang: localStorage.getItem('lang') || 'es',
                
                t: {
                    es: {
                        back: 'Volver a la tienda',
                        myProfile: 'Mi Perfil',
                        logout: 'Cerrar Sesión',
                        personalData: 'Datos Personales',
                        update: 'Actualizar Datos',
                        orderHistory: 'Historial de Pedidos',
                        order: 'Pedido', date: 'Fecha', total: 'Total', status: 'Estado',
                        st: { 0: 'Pendiente', 1: 'Pagado', 2: 'Enviado', 3: 'Cancelado' },
                        saved: '¡Datos actualizados correctamente!',
                        noOrders: 'Aún no has realizado compras.',
                        lbl: { name: 'Nombre', phone: 'Teléfono', address: 'Dirección' }
                    },
                    en: {
                        back: 'Back to store',
                        myProfile: 'My Profile',
                        logout: 'Logout',
                        personalData: 'Personal Data',
                        update: 'Update Info',
                        orderHistory: 'Order History',
                        order: 'Order', date: 'Date', total: 'Total', status: 'Status',
                        st: { 0: 'Pending', 1: 'Paid', 2: 'Shipped', 3: 'Canceled' },
                        saved: 'Data updated successfully!',
                        noOrders: 'You have not made any purchases yet.',
                        lbl: { name: 'Name', phone: 'Phone', address: 'Address' }
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
                logout() {
                    localStorage.removeItem('cliente_perpetua');
                    window.location.href = 'index.php'; // PHP session will be handled by index logic if needed or create logout.php
                }
            }
        }
    </script>
    <style>
        .glass { backdrop-filter: blur(14px); background: rgba(255, 255, 255, 0.8); border: 1px solid rgba(30, 58, 138, 0.1); }
        .dark .glass { background: rgba(15, 23, 42, 0.8); border: 1px solid rgba(255, 255, 255, 0.1); }
        .btn-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #22d3ee 100%); }
    </style>
</head>
<body class="min-h-screen transition-colors duration-500 bg-slate-50 dark:bg-gray-900 text-gray-900 dark:text-white font-sans">

    <div class="max-w-6xl mx-auto px-4 py-10">
        <div class="flex justify-between items-center mb-10">
            <a href="index.php" class="flex items-center gap-2 text-perpetua-blue dark:text-perpetua-aqua font-bold hover:underline">
                <i data-lucide="arrow-left" class="w-5 h-5"></i> <span x-text="t[lang].back"></span>
            </a>
            <div class="flex items-center gap-4">
                <button @click="switchLang()" class="text-[10px] font-bold border-2 border-perpetua-blue text-perpetua-blue dark:text-perpetua-aqua px-3 py-1 rounded-full">
                    <span x-text="lang === 'es' ? 'English' : 'Español'"></span>
                </button>
                <button @click="toggleDarkMode()" class="p-2 text-perpetua-blue dark:text-perpetua-aqua">
                    <i data-lucide="sun" x-show="darkMode"></i><i data-lucide="moon" x-show="!darkMode"></i>
                </button>
            </div>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-black uppercase text-perpetua-blue dark:text-perpetua-aqua flex items-center gap-3">
                    <i data-lucide="user-check" class="w-8 h-8"></i> <span x-text="t[lang].myProfile"></span>
                </h1>
                <p class="text-sm text-gray-500 font-bold mt-1">ID: #<?php echo str_pad($cliente_id, 5, "0", STR_PAD_LEFT); ?></p>
            </div>
            <button @click="logout()" class="text-red-500 font-bold text-sm flex items-center gap-2 hover:bg-red-50 dark:hover:bg-red-900/20 px-4 py-2 rounded-xl transition">
                <i data-lucide="log-out" class="w-4 h-4"></i> <span x-text="t[lang].logout"></span>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="glass p-8 rounded-[2rem] shadow-xl h-fit">
                <h2 class="text-xl font-black mb-6 uppercase flex items-center gap-2 text-gray-700 dark:text-white">
                    <i data-lucide="settings" class="w-5 h-5 text-perpetua-aqua"></i> <span x-text="t[lang].personalData"></span>
                </h2>

                <?php if($msg === 'ok'): ?>
                    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-xl text-xs font-bold text-center" x-text="t[lang].saved"></div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="text-[10px] font-bold uppercase text-gray-400 ml-2" x-text="t[lang].lbl.name"></label>
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($cliente['nombre']); ?>" class="w-full px-5 py-3 rounded-xl bg-white/50 dark:bg-gray-800 border dark:border-gray-700 focus:ring-2 focus:ring-perpetua-aqua outline-none">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase text-gray-400 ml-2">Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($cliente['email']); ?>" disabled class="w-full px-5 py-3 rounded-xl bg-gray-100 dark:bg-gray-900 border border-transparent text-gray-400 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase text-gray-400 ml-2" x-text="t[lang].lbl.phone"></label>
                        <input type="tel" name="telefono" value="<?php echo htmlspecialchars($cliente['telefono']); ?>" class="w-full px-5 py-3 rounded-xl bg-white/50 dark:bg-gray-800 border dark:border-gray-700 focus:ring-2 focus:ring-perpetua-aqua outline-none">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase text-gray-400 ml-2" x-text="t[lang].lbl.address"></label>
                        <textarea name="direccion" rows="3" class="w-full px-5 py-3 rounded-xl bg-white/50 dark:bg-gray-800 border dark:border-gray-700 focus:ring-2 focus:ring-perpetua-aqua outline-none"><?php echo htmlspecialchars($cliente['direccion']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="w-full btn-gradient text-white py-3 rounded-xl font-bold uppercase tracking-widest shadow-lg text-xs" x-text="t[lang].update"></button>
                </form>
            </div>

            <div class="lg:col-span-2 glass p-8 rounded-[2rem] shadow-xl">
                <h2 class="text-xl font-black mb-6 uppercase flex items-center gap-2 text-gray-700 dark:text-white">
                    <i data-lucide="package" class="w-5 h-5 text-perpetua-aqua"></i> <span x-text="t[lang].orderHistory"></span>
                </h2>

                <?php if(empty($pedidos)): ?>
                    <div class="text-center py-10 text-gray-400">
                        <i data-lucide="shopping-bag" class="w-12 h-12 mx-auto mb-2 opacity-50"></i>
                        <p x-text="t[lang].noOrders"></p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-[10px] uppercase text-gray-400 border-b dark:border-gray-700">
                                    <th class="py-3 px-4" x-text="t[lang].order"></th>
                                    <th class="py-3 px-4" x-text="t[lang].date"></th>
                                    <th class="py-3 px-4" x-text="t[lang].total"></th>
                                    <th class="py-3 px-4 text-right" x-text="t[lang].status"></th>
                                </tr>
                            </thead>
                            <tbody class="text-sm font-bold text-gray-700 dark:text-gray-200">
                                <?php foreach($pedidos as $p): ?>
                                <tr class="border-b dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                    <td class="py-4 px-4 text-perpetua-blue dark:text-perpetua-aqua">#PERP-<?php echo str_pad($p['id'], 5, "0", STR_PAD_LEFT); ?></td>
                                    <td class="py-4 px-4"><?php echo date('d/m/Y', strtotime($p['fecha'])); ?></td>
                                    <td class="py-4 px-4">$<?php echo number_format($p['total'], 2); ?></td>
                                    <td class="py-4 px-4 text-right">
                                        <span class="px-3 py-1 rounded-full text-[10px] uppercase tracking-wider text-white shadow-sm"
                                              :class="{
                                                  'bg-yellow-400': <?php echo $p['estado']; ?> == 0,
                                                  'bg-green-500': <?php echo $p['estado']; ?> == 1,
                                                  'bg-blue-500': <?php echo $p['estado']; ?> == 2,
                                                  'bg-red-500': <?php echo $p['estado']; ?> == 3
                                              }"
                                              x-text="t[lang].st[<?php echo $p['estado']; ?>]">
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
    <script>window.addEventListener('load', () => lucide.createIcons());</script>
</body>
</html>