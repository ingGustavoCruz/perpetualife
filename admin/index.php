<?php
/**
 * admin/index.php
 * Dashboard PRO: KPIs + Gráficas (Ventas, Ticket Promedio, Cupones) + Tops
 */

require_once 'verificar_sesion.php';
require_once '../api/conexion.php';

try {
    // --- NUEVO: PRODUCTOS CON STOCK BAJO ---
    $umbralStock = 5;
    $sqlStockBajo = "SELECT nombre, stock FROM kaiexper_perpetualife.productos WHERE stock <= $umbralStock ORDER BY stock ASC";
    $resStockBajo = $conn->query($sqlStockBajo);

    // --- 1. KPIS GENERALES ---
    $sqlKPI1 = "SELECT SUM(total) as total_ventas FROM kaiexper_perpetualife.pedidos WHERE estado IN ('COMPLETADO', 'PAGADO')";
    $resKPI1 = $conn->query($sqlKPI1);
    $totalIngresos = $resKPI1->fetch_assoc()['total_ventas'] ?? 0;

    $sqlKPI2 = "SELECT COUNT(*) as num_pedidos FROM kaiexper_perpetualife.pedidos";
    $resKPI2 = $conn->query($sqlKPI2);
    $totalPedidos = $resKPI2->fetch_assoc()['num_pedidos'] ?? 0;
    
    $sqlKPI3 = "SELECT COUNT(*) as num_clientes FROM kaiexper_perpetualife.clientes";
    $resKPI3 = $conn->query($sqlKPI3);
    $totalClientes = $resKPI3->fetch_assoc()['num_clientes'] ?? 0;

    // --- 2. DATOS PARA GRÁFICAS DE TENDENCIA (Ventas y Ticket Promedio) ---
    $sqlChart = "SELECT DATE_FORMAT(fecha, '%Y-%m') as mes, SUM(total) as total, AVG(total) as promedio 
                 FROM kaiexper_perpetualife.pedidos 
                 WHERE estado IN ('COMPLETADO', 'PAGADO') 
                 GROUP BY mes 
                 ORDER BY mes DESC LIMIT 6";
    $resChart = $conn->query($sqlChart);
    
    $meses = [];
    $ventas = [];
    $ticketsAvg = [];
    while($row = $resChart->fetch_assoc()){
        $meses[] = date('M Y', strtotime($row['mes'] . '-01'));
        $ventas[] = $row['total'];
        $ticketsAvg[] = round($row['promedio'], 2);
    }
    $meses = array_reverse($meses);
    $ventas = array_reverse($ventas);
    $ticketsAvg = array_reverse($ticketsAvg);

    // --- 3. DATOS CONVERSIÓN CUPONES (Dona) ---
    $sqlCupones = "SELECT COUNT(*) as con_cupon FROM kaiexper_perpetualife.pedidos WHERE cupon IS NOT NULL AND cupon != ''";
    $conCupon = $conn->query($sqlCupones)->fetch_assoc()['con_cupon'] ?? 0;
    $sinCupon = max(0, $totalPedidos - $conCupon);

    // --- 4. TOP 5 PRODUCTOS VENDIDOS ---
    $sqlTopProd = "SELECT p.nombre, SUM(dp.cantidad) as cantidad_total 
                   FROM kaiexper_perpetualife.detalles_pedido dp
                   JOIN kaiexper_perpetualife.productos p ON dp.producto_id = p.id
                   JOIN kaiexper_perpetualife.pedidos ped ON dp.pedido_id = ped.id
                   WHERE ped.estado IN ('COMPLETADO', 'PAGADO')
                   GROUP BY p.id
                   ORDER BY cantidad_total DESC LIMIT 5";
    $resTopProd = $conn->query($sqlTopProd);

    // --- 5. TOP 5 ESTADOS CON MÁS VENTAS ---
    $sqlTopEstados = "SELECT c.estado, COUNT(p.id) as total_compras, SUM(p.total) as monto_total
                      FROM kaiexper_perpetualife.clientes c
                      JOIN kaiexper_perpetualife.pedidos p ON c.id = p.cliente_id
                      WHERE p.estado IN ('COMPLETADO', 'PAGADO') AND c.estado IS NOT NULL AND c.estado != ''
                      GROUP BY c.estado
                      ORDER BY total_compras DESC LIMIT 5";
    $resTopEstados = $conn->query($sqlTopEstados);

    // --- 6. TOP 5 CLIENTES (VIP) ---
    $sqlTopClient = "SELECT c.nombre, SUM(p.total) as gastado, COUNT(p.id) as compras
                     FROM kaiexper_perpetualife.clientes c
                     JOIN kaiexper_perpetualife.pedidos p ON c.id = p.cliente_id
                     WHERE p.estado IN ('COMPLETADO', 'PAGADO')
                     GROUP BY c.id
                     ORDER BY gastado DESC LIMIT 5";
    $resTopClient = $conn->query($sqlTopClient);

    // --- 7. LISTADO DE PEDIDOS RECIENTES ---
    $queryRecientes = "SELECT p.id, p.fecha, p.total, p.estado, c.nombre 
                       FROM kaiexper_perpetualife.pedidos p
                       LEFT JOIN kaiexper_perpetualife.clientes c ON p.cliente_id = c.id
                       ORDER BY p.fecha DESC LIMIT 5";
    $resRecientes = $conn->query($queryRecientes);

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
    <link rel="icon" type="image/png" href="../imagenes/monito01.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-slate-100 font-sans text-slate-900 h-screen flex overflow-hidden" x-data="{ sidebarOpen: false }">

    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition.opacity class="fixed inset-0 bg-slate-900/50 z-40 md:hidden backdrop-blur-sm"></div>

    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white text-slate-600 flex flex-col transition-transform duration-300 ease-in-out md:translate-x-0 md:static flex-shrink-0 border-r border-slate-200 shadow-sm"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="p-6 flex flex-col items-center border-b border-slate-100 relative">
            <button @click="sidebarOpen = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-800 md:hidden">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
            <img src="../imagenes/Perpetua_Life.png" class="hidden lg:block w-40 object-contain mb-2">
            <img src="../imagenes/logoPerpetua.png" class="block lg:hidden w-12 object-contain mb-2">
            <span class="text-cyan-600 font-black tracking-widest text-xl mt-1">ADMIN</span>
            <p class="text-sm font-bold text-[#1e3a8a] mt-2 text-center">
                Bienvenid@ <?php echo htmlspecialchars($_SESSION['admin_nombre']); ?>
            </p>
        </div>
        
        <nav class="flex-1 px-4 space-y-3 overflow-y-auto mt-6">
            <a href="index.php" class="flex items-center gap-3 text-cyan-700 font-bold bg-cyan-50 p-3 rounded-xl border border-cyan-100">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="pedidos.php" class="flex items-center gap-3 text-slate-500 hover:text-slate-900 transition p-3 rounded-xl hover:bg-slate-50 font-medium">
                <i data-lucide="shopping-bag"></i> Pedidos</a>
            <a href="productos.php" class="flex items-center gap-3 text-slate-500 hover:text-slate-900 transition p-3 rounded-xl hover:bg-slate-50 font-medium">
                <i data-lucide="package"></i> Productos
            </a>
            <a href="cupones.php" class="flex items-center gap-3 text-slate-500 hover:text-slate-900 transition p-3 rounded-xl hover:bg-slate-50 font-medium">
                <i data-lucide="ticket"></i> Cupones
            </a>
            <a href="envios.php" class="flex items-center gap-3 text-slate-500 hover:text-slate-900 transition p-3 rounded-xl hover:bg-slate-50 font-medium">
                <i data-lucide="truck"></i> Costos de Envío
            </a>
            <a href="clientes.php" class="flex items-center gap-3 text-slate-500 hover:text-slate-900 transition p-3 rounded-xl hover:bg-slate-50 font-medium">
                <i data-lucide="users"></i> Clientes
            </a>

            <?php if (isset($_SESSION['admin_rol']) && $_SESSION['admin_rol'] === 'superadmin'): ?>
                <div class="pt-4 mt-4 border-t border-slate-100">
                    <p class="px-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Administración</p>
                    <a href="usuarios.php" class="flex items-center gap-3 text-slate-500 hover:text-purple-600 transition p-3 rounded-xl hover:bg-purple-50 font-medium">
                        <i data-lucide="shield"></i> Usuarios Staff
                    </a>
                    <a href="bitacora.php" class="flex items-center gap-3 text-slate-500 hover:text-orange-600 transition p-3 rounded-xl hover:bg-orange-50 font-medium">
                        <i data-lucide="file-warning"></i> Bitácora (Logs)
                    </a>
                </div>
            <?php endif; ?>
        </nav>

        <div class="p-4 border-t border-slate-100">
            <a href="logout.php" class="flex items-center gap-3 text-red-500 hover:text-red-700 font-bold p-3 transition-colors hover:bg-red-50 rounded-xl">
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
                    <p class="text-slate-500 text-sm hidden md:block">Visión general del negocio</p>
                </div>
            </div>
            <div class="flex items-center gap-4 self-end md:self-auto">
                <span class="bg-emerald-100 text-emerald-700 px-4 py-1 rounded-full text-xs font-bold uppercase flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> <span class="hidden sm:inline">Sistema</span> Online
                </span>
            </div>
        </header>

        <?php if($resStockBajo && $resStockBajo->num_rows > 0): ?>
        <div class="mb-8 bg-red-50 border-l-8 border-red-500 p-6 rounded-2xl shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="bg-red-100 p-3 rounded-full text-red-600 animate-pulse">
                    <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                </div>
                <div>
                    <h4 class="text-red-800 font-black uppercase text-sm tracking-widest">Atención: Inventario Crítico</h4>
                    <p class="text-red-600 text-xs font-medium">Hay <?php echo $resStockBajo->num_rows; ?> productos que están a punto de agotarse.</p>
                </div>
            </div>
            <div class="hidden md:flex gap-2 overflow-x-auto max-w-md">
                <?php while($item = $resStockBajo->fetch_assoc()): ?>
                    <span class="bg-white px-3 py-1 rounded-lg text-[10px] font-bold border border-red-200 text-slate-700 whitespace-nowrap">
                        <?php echo $item['nombre']; ?>: <span class="text-red-600"><?php echo $item['stock']; ?></span>
                    </span>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-cyan-500 p-6 rounded-2xl shadow-lg text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-blue-100 font-bold text-xs uppercase tracking-wider mb-1">Ingresos Totales</p>
                        <h3 class="text-3xl font-black">$<?php echo number_format($totalIngresos, 2); ?></h3>
                    </div>
                    <div class="bg-white/20 p-2 rounded-lg"><i data-lucide="dollar-sign" class="w-6 h-6"></i></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-slate-400 font-bold text-xs uppercase tracking-wider mb-1">Pedidos Totales</p>
                        <h3 class="text-3xl font-black text-slate-800"><?php echo $totalPedidos; ?></h3>
                    </div>
                    <div class="bg-purple-50 p-2 rounded-lg text-purple-600"><i data-lucide="shopping-bag" class="w-6 h-6"></i></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-slate-400 font-bold text-xs uppercase tracking-wider mb-1">Clientes</p>
                        <h3 class="text-3xl font-black text-slate-800"><?php echo $totalClientes; ?></h3>
                    </div>
                    <div class="bg-emerald-50 p-2 rounded-lg text-emerald-600"><i data-lucide="users" class="w-6 h-6"></i></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200">
                <h3 class="font-bold text-lg text-slate-700 mb-4 flex items-center gap-2">
                    <i data-lucide="bar-chart-2" class="text-cyan-500 w-5 h-5"></i> Ventas Mensuales ($)
                </h3>
                <div class="relative h-64 w-full">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200">
                <h3 class="font-bold text-lg text-slate-700 mb-4 flex items-center gap-2">
                    <i data-lucide="trending-up" class="text-blue-500 w-5 h-5"></i> Ticket Promedio ($)
                </h3>
                <div class="relative h-64 w-full">
                    <canvas id="avgTicketChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200">
                <h3 class="font-bold text-lg text-slate-700 mb-4 flex items-center gap-2">
                    <i data-lucide="ticket" class="text-purple-500 w-5 h-5"></i> Uso de Cupones
                </h3>
                <div class="relative h-64 w-full">
                    <canvas id="couponChart"></canvas>
                </div>
            </div>

            <div class="space-y-8 lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-8 space-y-0">
                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-200">
                    <h3 class="font-bold text-lg text-slate-700 mb-4 flex items-center gap-2">
                        <i data-lucide="star" class="text-yellow-500 w-5 h-5"></i> Top Productos
                    </h3>
                    <div class="space-y-4">
                        <?php if($resTopProd && $resTopProd->num_rows > 0): ?>
                            <?php while($prod = $resTopProd->fetch_assoc()): ?>
                            <div class="flex items-center justify-between border-b border-slate-50 pb-2 last:border-0">
                                <span class="text-sm font-medium text-slate-600 truncate w-2/3"><?php echo $prod['nombre']; ?></span>
                                <span class="text-xs font-bold bg-cyan-50 text-cyan-700 px-2 py-1 rounded-md"><?php echo $prod['cantidad_total']; ?> unds.</span>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-xs text-slate-400 italic">No hay datos aún.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-200">
                    <h3 class="font-bold text-lg text-slate-700 mb-4 flex items-center gap-2">
                        <i data-lucide="map-pin" class="text-emerald-500 w-5 h-5"></i> Top Estados
                    </h3>
                    <div class="space-y-4">
                        <?php if($resTopEstados && $resTopEstados->num_rows > 0): ?>
                            <?php while($edo = $resTopEstados->fetch_assoc()): ?>
                            <div class="flex items-center justify-between border-b border-slate-50 pb-2 last:border-0">
                                <span class="text-sm font-medium text-slate-600 truncate w-2/3"><?php echo $edo['estado']; ?></span>
                                <div class="text-right">
                                    <span class="block text-xs font-bold text-emerald-700"><?php echo $edo['total_compras']; ?> ventas</span>
                                    <span class="block text-[10px] text-slate-400">$<?php echo number_format($edo['monto_total'], 0); ?></span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-xs text-slate-400 italic">Esperando primeras ventas...</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200">
                <h3 class="font-bold text-lg text-slate-700 mb-4 flex items-center gap-2">
                    <i data-lucide="crown" class="text-purple-500 w-5 h-5"></i> Clientes VIP
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="p-3 text-[10px] font-black uppercase text-slate-400">Cliente</th>
                                <th class="p-3 text-[10px] font-black uppercase text-slate-400">Compras</th>
                                <th class="p-3 text-[10px] font-black uppercase text-slate-400 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if($resTopClient && $resTopClient->num_rows > 0): ?>
                                <?php while($vip = $resTopClient->fetch_assoc()): ?>
                                <tr>
                                    <td class="p-3 text-sm font-bold text-slate-700"><?php echo $vip['nombre']; ?></td>
                                    <td class="p-3 text-xs text-slate-500"><?php echo $vip['compras']; ?> órdenes</td>
                                    <td class="p-3 text-sm font-black text-emerald-600 text-right">$<?php echo number_format($vip['gastado'], 0); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="p-4 text-center text-xs text-slate-400">Sin datos.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-slate-200">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-lg text-slate-700 flex items-center gap-2">
                        <i data-lucide="clock" class="text-slate-400 w-5 h-5"></i> Recientes
                    </h3>
                    <a href="pedidos.php" class="text-xs font-bold text-cyan-600 hover:underline">Ver todo</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-slate-100">
                            <?php if($resRecientes && $resRecientes->num_rows > 0): ?>
                                <?php while($rec = $resRecientes->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50 transition cursor-pointer" onclick="window.location='ver_pedido.php?id=<?php echo $rec['id']; ?>'">
                                    <td class="p-3">
                                        <div class="font-bold text-xs text-slate-800">#<?php echo $rec['id']; ?></div>
                                        <div class="text-[10px] text-slate-400"><?php echo date('d/m', strtotime($rec['fecha'])); ?></div>
                                    </td>
                                    <td class="p-3 text-xs text-slate-600"><?php echo $rec['nombre'] ?: 'Invitado'; ?></td>
                                    <td class="p-3">
                                        <?php 
                                            $bg = 'bg-slate-100 text-slate-600';
                                            if($rec['estado'] == 'COMPLETADO' || $rec['estado'] == 'PAGADO') $bg = 'bg-emerald-100 text-emerald-600';
                                            if($rec['estado'] == 'PENDIENTE') $bg = 'bg-yellow-100 text-yellow-600';
                                        ?>
                                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase <?php echo $bg; ?>"><?php echo $rec['estado']; ?></span>
                                    </td>
                                    <td class="p-3 text-right font-black text-xs text-slate-800">$<?php echo number_format($rec['total'], 0); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="p-4 text-center text-xs text-slate-400">Sin pedidos recientes.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        lucide.createIcons();

        // 1. Gráfica de Ventas
        const salesCtx = document.getElementById('salesChart');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($meses); ?>,
                datasets: [{
                    label: 'Ventas ($)',
                    data: <?php echo json_encode($ventas); ?>,
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6, 182, 212, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#06b6d4',
                    pointRadius: 4,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4], color: '#e2e8f0' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Gráfica de Ticket Promedio
        const avgCtx = document.getElementById('avgTicketChart');
        new Chart(avgCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($meses); ?>,
                datasets: [{
                    label: 'Ticket Promedio ($)',
                    data: <?php echo json_encode($ticketsAvg); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3b82f6',
                    pointRadius: 4,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4], color: '#e2e8f0' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 3. Gráfica de Cupones (Dona)
        const couponCtx = document.getElementById('couponChart');
        new Chart(couponCtx, {
            type: 'doughnut',
            data: {
                labels: ['Con Cupón', 'Sin Cupón'],
                datasets: [{
                    data: [<?php echo $conCupon; ?>, <?php echo $sinCupon; ?>],
                    backgroundColor: ['#a855f7', '#e2e8f0'],
                    hoverOffset: 4,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                }
            }
        });
    </script>
</body>
</html>