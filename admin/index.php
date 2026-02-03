<?php require_once 'verificar_sesion.php'; ?>
<?php
// admin/index.php
require_once '../api/conexion.php';

// Consulta para obtener pedidos con los datos del cliente
$query = "SELECT p.id, p.fecha, p.total, p.moneda, p.estado, c.nombre, c.telefono, c.email 
          FROM kaiexper_perpetualife.pedidos p
          JOIN kaiexper_perpetualife.clientes c ON p.cliente_id = c.id
          ORDER BY p.fecha DESC";

$resultado = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Perpetualife</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-100 font-sans text-slate-900">

    <div class="flex min-h-screen">
        <aside class="w-64 bg-slate-900 text-white p-6 hidden md:block">
            <h1 class="text-xl font-black mb-10 tracking-tighter">PERPETUALIFE <span class="text-cyan-400">ADMIN</span></h1>
            <nav class="space-y-4">
                <a href="#" class="flex items-center gap-3 text-cyan-400 font-bold bg-white/5 p-3 rounded-xl">
                    <i data-lucide="shopping-bag"></i> Ventas
                </a>
                <a href="#" class="flex items-center gap-3 text-slate-400 hover:text-white transition p-3">
                    <i data-lucide="package"></i> Productos
                </a>
                <div class="mt-auto pt-10 border-t border-slate-800">
                    <a href="logout.php" class="flex items-center gap-3 text-red-400 hover:text-red-300 font-bold p-3 transition-colors">
                        <i data-lucide="log-out" class="w-5 h-5"></i> Cerrar Sesión
                    </a>
                </div>
            </nav>
        </aside>

        <main class="flex-1 p-8">
            <header class="flex justify-between items-center mb-10">
                <h2 class="text-3xl font-black">Ventas Recientes</h2>
                <div class="flex items-center gap-4">
                    <span class="bg-emerald-100 text-emerald-700 px-4 py-1 rounded-full text-xs font-bold uppercase">Sistema Online</span>
                </div>
            </header>

            <div class="bg-white rounded-[2rem] shadow-xl overflow-hidden border border-slate-200">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-xs font-black uppercase text-slate-400">ID Pedido</th>
                            <th class="px-6 py-4 text-xs font-black uppercase text-slate-400">Cliente</th>
                            <th class="px-6 py-4 text-xs font-black uppercase text-slate-400">Contacto</th>
                            <th class="px-6 py-4 text-xs font-black uppercase text-slate-400">Fecha</th>
                            <th class="px-6 py-4 text-xs font-black uppercase text-slate-400">Total</th>
                            <th class="px-6 py-4 text-xs font-black uppercase text-slate-400">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php while($row = $resultado->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-500">#PERP-<?php echo str_pad($row['id'], 5, "0", STR_PAD_LEFT); ?></td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800"><?php echo $row['nombre']; ?></div>
                                <div class="text-xs text-slate-400"><?php echo $row['email']; ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <a href="https://wa.me/<?php echo $row['telefono']; ?>" target="_blank" class="flex items-center gap-2 text-emerald-600 font-bold text-sm">
                                    <i data-lucide="phone" class="w-4 h-4"></i> <?php echo $row['telefono']; ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500"><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                            <td class="px-6 py-4 font-black text-slate-900"><?php echo number_format($row['total'], 2) . ' ' . $row['moneda']; ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-blue-100 text-blue-600">
                                    <?php echo $row['estado']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>