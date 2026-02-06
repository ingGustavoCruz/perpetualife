<?php
/**
 * api/confirmar_pago.php
 * VERSIÓN V1.1 - CONCURRENCIA DE STOCK (RACE CONDITION) SOLUCIONADA
 * * Cambios:
 * - Prefijos de BD explícitos (kaiexper_perpetualife.)
 * - Lógica Optimistic Locking en el descuento de stock.
 */

// 1. CONFIGURACIÓN INICIAL
require_once 'conexion.php';
require_once 'mailer.php'; 

header('Content-Type: application/json');

// Función HÍBRIDA: Escribe en TXT y en Base de Datos (Bitácora)
function registrarLog($mensaje, $tipo = 'INFO') {
    global $conn; // Usamos la conexión global

    // 1. Respaldo en archivo TXT (Por si la BD falla)
    $rutaLog = __DIR__ . '/log_transacciones.txt';
    file_put_contents($rutaLog, date('Y-m-d H:i:s') . " - [$tipo] " . $mensaje . "\n", FILE_APPEND);

    // 2. Insertar en Bitácora (admin_logs)
    // Asumimos ID 0 para el SISTEMA. Asegúrate de que tu BD acepte ID 0 o no tenga restricción de llave foránea estricta.
    // Si tienes restricción, crea un usuario "System" en admin_usuarios y usa su ID aquí.
    try {
        if ($conn && !$conn->connect_error) {
            $modulo = 'CHECKOUT_API';
            $accion = ($tipo === 'ERROR') ? 'FALLO' : 'PROCESO';
            
            // Usamos prepared statements para la bitácora también
            $stmtLog = $conn->prepare("INSERT INTO kaiexper_perpetualife.admin_logs (admin_id, admin_nombre, modulo, accion, descripcion, fecha) VALUES (0, 'SISTEMA', ?, ?, ?, NOW())");
            $stmtLog->bind_param("sss", $modulo, $accion, $mensaje);
            $stmtLog->execute();
            $stmtLog->close();
        }
    } catch (Exception $e) {
        // Si falla el log en BD, ya tenemos el TXT, no detenemos el flujo.
    }
}

// 2. RECEPCIÓN DE DATOS
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    registrarLog("Error: JSON vacío o inválido.");
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit;
}

// Variables iniciales
$orderID = $data['orderID'] ?? 'MANUAL-' . time();
$cart = $data['cart'] ?? [];
$cliente = $data['cliente'] ?? [];
$crearCuenta = $data['crear_cuenta'] ?? false;
$newPassword = $data['new_password'] ?? '';
$cuponCodigo = isset($data['cupon']) ? strtoupper(trim($data['cupon'])) : null;

// Validación mínima
if (empty($cliente['email']) || empty($cliente['estado'])) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos del cliente (Email o Estado)']);
    exit;
}

registrarLog("Iniciando pedido para: " . $cliente['email'] . " | Estado: " . $cliente['estado']);

$conn->begin_transaction();

try {
    // =========================================================================
    // PASO 1: PRE-VALIDACIÓN DE STOCK (Lectura inicial)
    // =========================================================================
    // Aunque validamos de nuevo al final (Paso 6), esta lectura evita procesar 
    // todo si ya sabemos que no hay stock desde el principio.
    $subtotalReal = 0;
    $itemsProcesados = []; 

    $stmtProd = $conn->prepare("SELECT id, nombre, precio, stock FROM kaiexper_perpetualife.productos WHERE id = ?");

    foreach ($cart as $item) {
        $idProd = intval($item['id']);
        $qty = intval($item['qty']);
        if($qty <= 0) continue;

        $stmtProd->bind_param("i", $idProd);
        $stmtProd->execute();
        $resProd = $stmtProd->get_result();
        
        if ($prodBD = $resProd->fetch_assoc()) {
            if ($prodBD['stock'] < $qty) {
                throw new Exception("Stock insuficiente para: " . $prodBD['nombre']);
            }

            $precioReal = floatval($prodBD['precio']);
            $subtotalReal += ($precioReal * $qty);

            $itemsProcesados[] = [
                'id' => $prodBD['id'],
                'nombre' => $prodBD['nombre'],
                'precio' => $precioReal,
                'cantidad' => $qty
            ];
        } else {
            throw new Exception("Producto ID $idProd no válido.");
        }
    }
    $stmtProd->close();

    if(empty($itemsProcesados)) throw new Exception("Carrito vacío tras validación.");


    // =========================================================================
    // PASO 2: CÁLCULO DE ENVÍO DINÁMICO
    // =========================================================================
    $costoEnvio = 0;
    $zonaEncontrada = false;
    $estadoCliente = $cliente['estado'];

    // Consulta con prefijo de BD
    $resEnvios = $conn->query("SELECT id, costo, estados FROM kaiexper_perpetualife.config_envios ORDER BY costo ASC");

    while ($row = $resEnvios->fetch_assoc()) {
        $listaEstados = json_decode($row['estados'], true);
        
        if (is_array($listaEstados)) {
            if (in_array($estadoCliente, $listaEstados)) {
                $costoEnvio = floatval($row['costo']);
                $zonaEncontrada = true;
                registrarLog("Zona Detectada: ID {$row['id']} - Costo: $$costoEnvio");
                break; 
            }
        }
    }

    // Fallback Zona Nacional (ID 3)
    if (!$zonaEncontrada) {
        $resDefault = $conn->query("SELECT costo FROM kaiexper_perpetualife.config_envios WHERE id = 3");
        if ($rowDef = $resDefault->fetch_assoc()) {
            $costoEnvio = floatval($rowDef['costo']);
            registrarLog("Zona no encontrada. Aplicando tarifa default: $$costoEnvio");
        } else {
            $costoEnvio = 350.00; 
        }
    }


    // =========================================================================
    // PASO 3: CUPONES Y DESCUENTOS
    // =========================================================================
    $descuentoReal = 0;
    $idCuponAplicado = null;
    $infoCupon = "";
    
    if ($cuponCodigo) {
        $stmtCup = $conn->prepare("SELECT * FROM kaiexper_perpetualife.cupones WHERE codigo = ? LIMIT 1");
        $stmtCup->bind_param("s", $cuponCodigo);
        $stmtCup->execute();
        $resCup = $stmtCup->get_result();

        if ($rowCup = $resCup->fetch_assoc()) {
            $hoy = date('Y-m-d');
            if ($rowCup['estado_manual'] === 'activo' && 
                $rowCup['fecha_vencimiento'] >= $hoy && 
                ($rowCup['limite_uso'] == 0 || $rowCup['usos_actuales'] < $rowCup['limite_uso'])) {

                $idCuponAplicado = $rowCup['id'];
                $infoCupon = $cuponCodigo;
                $tipoOferta = $rowCup['tipo_oferta'];

                // Cálculo Descuento
                if ($tipoOferta === 'descuento' || $tipoOferta === 'ambos') {
                    if ($rowCup['tipo'] === 'porcentaje') {
                        $descuentoReal = $subtotalReal * ($rowCup['descuento'] / 100);
                    } else {
                        $descuentoReal = floatval($rowCup['descuento']);
                    }
                }
                // Cálculo Envío
                if ($tipoOferta === 'envio' || $tipoOferta === 'ambos') {
                    $costoEnvio = 0; 
                    $infoCupon .= " (Envío Gratis)";
                }
            }
        }
        $stmtCup->close();
    }

    $totalCalculado = ($subtotalReal - $descuentoReal) + $costoEnvio;
    if ($totalCalculado < 0) $totalCalculado = 0;


    // =========================================================================
    // PASO 4: GESTIÓN DE CLIENTE
    // =========================================================================
    $email = $cliente['email'];
    $nombre = $cliente['nombre'];
    $telefono = $cliente['telefono'];
    $direccion = $cliente['direccion'];
    $estadoClienteDB = $cliente['estado'];

    $stmtCheck = $conn->prepare("SELECT id FROM kaiexper_perpetualife.clientes WHERE email = ?");
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($row = $resCheck->fetch_assoc()) {
        $cliente_id = $row['id'];
        if ($crearCuenta && !empty($newPassword)) {
            $passHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmtUpd = $conn->prepare("UPDATE kaiexper_perpetualife.clientes SET nombre=?, telefono=?, estado=?, direccion=?, password=? WHERE id=?");
            $stmtUpd->bind_param("sssssi", $nombre, $telefono, $estadoClienteDB, $direccion, $passHash, $cliente_id);
        } else {
            $stmtUpd = $conn->prepare("UPDATE kaiexper_perpetualife.clientes SET nombre=?, telefono=?, estado=?, direccion=? WHERE id=?");
            $stmtUpd->bind_param("ssssi", $nombre, $telefono, $estadoClienteDB, $direccion, $cliente_id);
        }
        $stmtUpd->execute();
    } else {
        $passHash = ($crearCuenta && !empty($newPassword)) ? password_hash($newPassword, PASSWORD_DEFAULT) : null;
        $stmtIns = $conn->prepare("INSERT INTO kaiexper_perpetualife.clientes (nombre, email, telefono, direccion, estado, password, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmtIns->bind_param("ssssss", $nombre, $email, $telefono, $direccion, $estadoClienteDB, $passHash);
        $stmtIns->execute();
        $cliente_id = $conn->insert_id;
    }


    // =========================================================================
    // PASO 5: INSERTAR PEDIDO
    // =========================================================================
    $sql_pedido = "INSERT INTO kaiexper_perpetualife.pedidos (cliente_id, fecha, total, estado, metodo_pago, id_transaccion, paypal_order_id, moneda, cupon) VALUES (?, NOW(), ?, 'COMPLETADO', 'PayPal', ?, ?, 'MXN', ?)";
    $stmtPed = $conn->prepare($sql_pedido);
    $stmtPed->bind_param("idsss", $cliente_id, $totalCalculado, $orderID, $orderID, $cuponCodigo);
    
    if (!$stmtPed->execute()) throw new Exception("Error al guardar pedido: " . $stmtPed->error);
    $pedido_id = $conn->insert_id;


    // =========================================================================
    // PASO 6: DETALLES Y DESCUENTO DE STOCK (RACE CONDITION FIX)
    // =========================================================================
    $stmtDet = $conn->prepare("INSERT INTO kaiexper_perpetualife.detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    
    // AQUÍ ESTÁ LA MAGIA: "AND stock >= ?"
    // Esto asegura que la DB solo reste si tiene suficiente en ese milisegundo exacto.
    $stmtStk = $conn->prepare("UPDATE kaiexper_perpetualife.productos SET stock = stock - ? WHERE id = ? AND stock >= ?");

    $listaProductosHTML = "";

    foreach ($itemsProcesados as $item) {
        // 1. Insertar Detalle
        $stmtDet->bind_param("iiid", $pedido_id, $item['id'], $item['cantidad'], $item['precio']);
        $stmtDet->execute();

        // 2. Restar Stock con Optimistic Locking
        // Bind: (Cantidad a restar, ID producto, Cantidad mínima requerida)
        $stmtStk->bind_param("iii", $item['cantidad'], $item['id'], $item['cantidad']);
        $stmtStk->execute();

        // Verificar si la DB realmente hizo el cambio
        if ($stmtStk->affected_rows === 0) {
            // ¡RACE CONDITION DETECTADA!
            // Alguien compró el último item milisegundos antes que este proceso.
            throw new Exception("Lo sentimos, el producto '{$item['nombre']}' se acaba de agotar.");
        }

        // HTML Correo
        $subtotalItem = number_format($item['precio'] * $item['cantidad'], 2);
        $listaProductosHTML .= "
            <tr style='border-bottom: 1px solid #eee;'>
                <td style='padding: 10px; color: #333;'>{$item['nombre']}</td>
                <td style='padding: 10px; text-align: center; color: #555;'>{$item['cantidad']}</td>
                <td style='padding: 10px; text-align: right; color: #333;'>$$subtotalItem</td>
            </tr>";
    }

    // =========================================================================
    // PASO 7: QUEMAR CUPÓN (OPCIONAL: TAMBIÉN SE PUEDE BLINDAR)
    // =========================================================================
    if ($idCuponAplicado) {
        $stmtCupUpd = $conn->prepare("UPDATE kaiexper_perpetualife.cupones SET usos_actuales = usos_actuales + 1 WHERE id = ?");
        $stmtCupUpd->bind_param("i", $idCuponAplicado);
        $stmtCupUpd->execute();
    }

    $conn->commit();


    // =========================================================================
    // PASO 8: ENVIAR CORREO
    // =========================================================================
    $filaDescuento = "";
    if ($descuentoReal > 0) {
        $filaDescuento = "<tr><td colspan='2' style='text-align:right; color:#ef4444; font-size:12px; padding:5px;'>Descuento:</td><td style='text-align:right; color:#ef4444; font-size:12px;'>-$$" . number_format($descuentoReal, 2) . "</td></tr>";
    }
    
    $filaEnvio = "<tr><td colspan='2' style='text-align:right; color:#555; font-size:12px; padding:5px;'>Envío:</td><td style='text-align:right; color:#555; font-size:12px;'>" . ($costoEnvio == 0 ? 'GRATIS' : '$' . number_format($costoEnvio, 2)) . "</td></tr>";

    $htmlCorreo = "
    <div style='font-family: sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 10px;'>
        <div style='background: #1e3a8a; color: white; padding: 20px; text-align: center;'>
            <h2>¡Gracias por tu compra!</h2>
            <p>Orden #$pedido_id</p>
        </div>
        <div style='padding: 20px;'>
            <p>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
            <p>Tu pedido se ha procesado correctamente.</p>
            <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                <thead>
                    <tr style='background: #f9f9f9; text-align: left;'>
                        <th style='padding: 10px;'>Producto</th>
                        <th style='padding: 10px; text-align: center;'>Cant.</th>
                        <th style='padding: 10px; text-align: right;'>Precio</th>
                    </tr>
                </thead>
                <tbody>$listaProductosHTML</tbody>
                <tfoot>
                    $filaEnvio
                    $filaDescuento
                    <tr>
                        <td colspan='2' style='text-align: right; font-weight: bold; padding: 15px; border-top: 2px solid #eee;'>TOTAL:</td>
                        <td style='text-align: right; font-weight: bold; color: #1e3a8a; font-size: 18px; border-top: 2px solid #eee;'>$$" . number_format($totalCalculado, 2) . "</td>
                    </tr>
                </tfoot>
            </table>
            <br>
            <p><strong>Dirección de Envío:</strong><br>" . htmlspecialchars($direccion) . "<br>" . htmlspecialchars($estadoClienteDB) . "</p>
        </div>
    </div>";

    if(function_exists('enviarCorreo')) {
        enviarCorreo($email, "Confirmación de Compra #PERP-" . str_pad($pedido_id, 5, "0", STR_PAD_LEFT), $htmlCorreo);
    }

    echo json_encode(['status' => 'success', 'pedido_id' => $pedido_id]);

} catch (Exception $e) {
    if ($conn->in_transaction) $conn->rollback();
    
    // Aquí cambiamos el tipo a 'ERROR' para que resalte en tu Bitácora
    registrarLog("ERROR FATAL: " . $e->getMessage(), 'ERROR');
    
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>