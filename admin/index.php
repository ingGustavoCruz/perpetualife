<?php
/**
 * admin/index.php
 * Dashboard con Menú Hamburguesa (Responsive)
 */

require_once 'verificar_sesion.php';
require_once '../api/conexion.php';

try {
    // KPIs
    $sqlKPI1 = "SELECT SUM(total) as total_ventas FROM pedidos WHERE estado = 'PAGADO'";
    $resKPI1 = $conn->query($sqlKPI1);
    $totalIngresos = $resKPI1->fetch_assoc()['total_ventas'] ?? 0;

    $sqlKPI2 = "SELECT COUNT(*) as num_pedidos FROM pedidos";
    $resKPI2 = $conn->query($sqlKPI2);
    $totalPedidos = $resKPI2->fetch_assoc()['num_pedidos'] ?? 0;

    // Listado
    $query = "SELECT p.id, p.fecha, p.total, p.moneda, p.estado, c.nombre, c.telefono, c.email 
              FROM pedidos p
              JOIN clientes c ON p.cliente_id = c.id
              ORDER BY p.fecha DESC";
    $resultado = $conn->query($query);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Perpetualife</title>
    <link rel="icon" type="image/png" href="../imagenes/KAI_NG.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-slate-100 font-sans text-slate-900 h-screen flex overflow-hidden" x-data="{ sidebarOpen: false }">

    <div x-show="sidebarOpen" 
         @click="sidebarOpen = false"
         x-transition.opacity
         class="fixed inset-0 bg-slate-900/50 z-40 md:hidden backdrop-blur-sm"></div>

    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white flex flex-col transition-transform duration-300 ease-in-out md:translate-x-0 md:static flex-shrink-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="p-6 flex flex-col items-center border-b border-slate-800 relative">
            <button @click="sidebarOpen = false" class="absolute top-4 right-4 text-slate-500 hover:text-white md:hidden">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>

            <img src="../imagenes/Perpetua_Life.png" alt="Perpetualife" class="hidden lg:block w-40 object-contain mb-2">
            <img src="../imagenes/logoPerpetua.png" alt="Perpetualife" class="block lg:hidden w-12 object-contain mb-2">
            
            <span class="text-cyan-400 font-black tracking-widest text-sm">ADMIN</span>
        </div>
        
        <nav class="flex-1 px-4 space-y-4 overflow-y-auto mt-6">
            <a href="index.php" class="flex items-center gap-3 text-cyan-400 font-bold bg-white/10 p-3 rounded-xl">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            
            <a href="productos.php" class="flex items-center gap-3 text-slate-400 hover:text-white transition p-3 rounded-xl hover:bg-white/5">
                <i data-lucide="package"></i> Productos
            </a>
            
            <a href="cupones.php" class="flex items-center gap-3 text-slate-400 hover:text-white transition p-3 rounded-xl hover:bg-white/5">
                <i data-lucide="ticket"></i> Cupones
            </a>

            <a href="clientes.php" class="flex items-center gap-3 text-slate-400 hover:text-white transition p-3 rounded-xl hover:bg-white/5">
                <i data-lucide="users"></i> Clientes
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <a href="logout.php" class="flex items-center gap-3 text-red-400 hover:text-red-300 font-bold p-3 transition-colors">
                <i data-lucide="log-out" class="w-5 h-5"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto w-full">
        
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" class="md:hidden p-2 bg-white rounded-xl shadow-sm text-slate-600 hover:text-cyan-600">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>

                <div>
                    <h2 class="text-2xl md:text-3xl font-black text-slate-800">Resumen</h2>
                    <p class="text-slate-500 text-sm hidden md:block">Bienvenido al panel de control</p>
                </div>
            </div>

            <div class="flex items-center gap-4 self-end md:self-auto">
                <span class="bg-emerald-100 text-emerald-700 px-4 py-1 rounded-full text-xs font-bold uppercase flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> <span class="hidden sm:inline">Sistema</span> Online
                </span>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-cyan-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase">Ingresos Totales</p>
                        <p class="text-2xl font-black text-slate-800">$<?php echo number_format($totalIngresos, 2); ?></p>
                    </div>
                    <div class="bg-cyan-50 p-3 rounded-lg text-cyan-600">
                        <i data-lucide="dollar-sign"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-purple-500">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase">Total Pedidos</p>
                        <p class="text-2xl font-black text-slate-800"><?php echo $totalPedidos; ?></p>
                    </div>
                    <div class="bg-purple-50 p-3 rounded-lg text-purple-600">
                        <i data-lucide="shopping-bag"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] shadow-xl overflow-hidden border border-slate-200">
            <div class="p-6 border-b border-slate-100">
                <h3 class="font-bold text-lg text-slate-700">Últimos Movimientos</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-xs font-black uppercase text-slate-400">ID</th>
                            <th class="px-6 py-4 text-xs font-black uppercase text-slate-400">Cliente</th>
                            <th class="px-6 py-4 text-xs font-black uppercase text-slate-400">Contacto</th>
                            <th class="px-6 py-4 text-xs font-black uppercase text-slate-400">Fecha</th>
                            <th class="px-6 py-4 text-xs font-black uppercase text-slate-400">Total</th>
                            <th class="px-6 py-4 text-xs font-black uppercase text-slate-400">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if ($resultado && $resultado->num_rows > 0): ?>
                            <?php while($row = $resultado->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition-colors group">
                                <td class="px-6 py-4 font-bold text-slate-500">#<?php echo str_pad($row['id'], 5, "0", STR_PAD_LEFT); ?></td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800"><?php echo $row['nombre']; ?></div>
                                    <div class="text-xs text-slate-400"><?php echo $row['email']; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $row['telefono']); ?>" target="_blank" 
                                       class="flex items-center gap-2 text-emerald-600 font-bold text-sm bg-emerald-50 px-3 py-1 rounded-lg hover:bg-emerald-100 transition w-fit">
                                        <i data-lucide="message-circle" class="w-4 h-4"></i> <?php echo $row['telefono']; ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    <?php echo date('d/m/Y', strtotime($row['fecha'])); ?>
                                </td>
                                <td class="px-6 py-4 font-black text-slate-900">$<?php echo number_format($row['total'], 2); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase 
                                        <?php echo ($row['estado'] === 'PAGADO') ? 'bg-blue-100 text-blue-600' : 'bg-yellow-100 text-yellow-600'; ?>">
                                        <?php echo $row['estado']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400 italic">Sin pedidos.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>