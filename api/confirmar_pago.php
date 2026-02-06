<?php
/**
 * api/confirmar_pago.php
 * VERSIÓN FINAL DE PRODUCCIÓN
 * - Seguridad: Prepared Statements (Blindado contra SQL Injection)
 * - Negocio: Validación de Stock Real + Cálculo de Envío Dinámico (JSON)
 */

// 1. CONFIGURACIÓN INICIAL
require_once 'conexion.php';
require_once 'mailer.php'; 

header('Content-Type: application/json');

// Función helper para logs de errores/transacciones
function registrarLog($mensaje) {
    $rutaLog = __DIR__ . '/log_transacciones.txt';
    file_put_contents($rutaLog, date('Y-m-d H:i:s') . " - " . $mensaje . "\n", FILE_APPEND);
}

// 2. RECEPCIÓN DE DATOS
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    registrarLog("Error: JSON vacío o inválido.");
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit;
}

// Extracción de variables (sin sanitizar aquí, lo hará bind_param después)
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
    // PASO 1: VALIDACIÓN DE PRODUCTOS Y STOCK (Server Side)
    // =========================================================================
    $subtotalReal = 0;
    $itemsProcesados = []; 

    $stmtProd = $conn->prepare("SELECT id, nombre, precio, stock FROM productos WHERE id = ?");

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
    // PASO 2: CÁLCULO DE ENVÍO DINÁMICO (Tabla config_envios)
    // =========================================================================
    $costoEnvio = 0;
    $zonaEncontrada = false;
    $estadoCliente = $cliente['estado']; // Ej: "Coahuila"

    // Consultamos la tabla de configuración
    $resEnvios = $conn->query("SELECT id, costo, estados FROM config_envios ORDER BY costo ASC");

    while ($row = $resEnvios->fetch_assoc()) {
        // La columna 'estados' es TEXT pero contiene un JSON
        $listaEstados = json_decode($row['estados'], true);
        
        if (is_array($listaEstados)) {
            // Buscamos coincidencia exacta con lo que envía el selector
            if (in_array($estadoCliente, $listaEstados)) {
                $costoEnvio = floatval($row['costo']);
                $zonaEncontrada = true;
                registrarLog("Zona Detectada: ID {$row['id']} - Costo: $$costoEnvio");
                break; 
            }
        }
    }

    // Fallback: Si no se encuentra, usar Zona Nacional (ID 3)
    if (!$zonaEncontrada) {
        $resDefault = $conn->query("SELECT costo FROM config_envios WHERE id = 3");
        if ($rowDef = $resDefault->fetch_assoc()) {
            $costoEnvio = floatval($rowDef['costo']);
            registrarLog("Zona no encontrada. Aplicando tarifa default (ID 3): $$costoEnvio");
        } else {
            $costoEnvio = 350.00; // Último recurso hardcoded
        }
    }


    // =========================================================================
    // PASO 3: CUPONES Y DESCUENTOS
    // =========================================================================
    $descuentoReal = 0;
    $idCuponAplicado = null;
    $infoCupon = "";
    
    if ($cuponCodigo) {
        $stmtCup = $conn->prepare("SELECT * FROM cupones WHERE codigo = ? LIMIT 1");
        $stmtCup->bind_param("s", $cuponCodigo);
        $stmtCup->execute();
        $resCup = $stmtCup->get_result();

        if ($rowCup = $resCup->fetch_assoc()) {
            $hoy = date('Y-m-d');
            // Validaciones de vigencia
            if ($rowCup['estado_manual'] === 'activo' && 
                $rowCup['fecha_vencimiento'] >= $hoy && 
                ($rowCup['limite_uso'] == 0 || $rowCup['usos_actuales'] < $rowCup['limite_uso'])) {

                $idCuponAplicado = $rowCup['id'];
                $infoCupon = $cuponCodigo;
                $tipoOferta = $rowCup['tipo_oferta'];

                // Descuento monetario
                if ($tipoOferta === 'descuento' || $tipoOferta === 'ambos') {
                    if ($rowCup['tipo'] === 'porcentaje') {
                        $descuentoReal = $subtotalReal * ($rowCup['descuento'] / 100);
                    } else {
                        $descuentoReal = floatval($rowCup['descuento']);
                    }
                }

                // Envío gratis
                if ($tipoOferta === 'envio' || $tipoOferta === 'ambos') {
                    $costoEnvio = 0; 
                    $infoCupon .= " (Envío Gratis)";
                }
            }
        }
        $stmtCup->close();
    }

    // CÁLCULO FINAL DEL TOTAL A COBRAR
    $totalCalculado = ($subtotalReal - $descuentoReal) + $costoEnvio;
    if ($totalCalculado < 0) $totalCalculado = 0;

    registrarLog("Sub: $subtotalReal | Desc: $descuentoReal | Env: $costoEnvio | Total: $totalCalculado");


    // =========================================================================
    // PASO 4: GUARDAR/ACTUALIZAR CLIENTE
    // =========================================================================
    $email = $cliente['email'];
    $nombre = $cliente['nombre'];
    $telefono = $cliente['telefono'];
    $direccion = $cliente['direccion'];
    $estadoClienteDB = $cliente['estado']; // Variable renombrada para evitar conflictos

    // Verificamos si existe
    $stmtCheck = $conn->prepare("SELECT id FROM clientes WHERE email = ?");
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($row = $resCheck->fetch_assoc()) {
        $cliente_id = $row['id'];
        // Update
        if ($crearCuenta && !empty($newPassword)) {
            $passHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmtUpd = $conn->prepare("UPDATE clientes SET nombre=?, telefono=?, estado=?, direccion=?, password=? WHERE id=?");
            $stmtUpd->bind_param("sssssi", $nombre, $telefono, $estadoClienteDB, $direccion, $passHash, $cliente_id);
        } else {
            $stmtUpd = $conn->prepare("UPDATE clientes SET nombre=?, telefono=?, estado=?, direccion=? WHERE id=?");
            $stmtUpd->bind_param("ssssi", $nombre, $telefono, $estadoClienteDB, $direccion, $cliente_id);
        }
        $stmtUpd->execute();
    } else {
        // Insert
        $passHash = ($crearCuenta && !empty($newPassword)) ? password_hash($newPassword, PASSWORD_DEFAULT) : null;
        $stmtIns = $conn->prepare("INSERT INTO clientes (nombre, email, telefono, direccion, estado, password, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmtIns->bind_param("ssssss", $nombre, $email, $telefono, $direccion, $estadoClienteDB, $passHash);
        $stmtIns->execute();
        $cliente_id = $conn->insert_id;
    }


    // =========================================================================
    // PASO 5: INSERTAR PEDIDO
    // =========================================================================
    // Nota: Guardamos el cupón usado para referencia futura
    $sql_pedido = "INSERT INTO pedidos (cliente_id, fecha, total, estado, metodo_pago, id_transaccion, paypal_order_id, moneda, cupon) VALUES (?, NOW(), ?, 'COMPLETADO', 'PayPal', ?, ?, 'MXN', ?)";
    $stmtPed = $conn->prepare($sql_pedido);
    $stmtPed->bind_param("idsss", $cliente_id, $totalCalculado, $orderID, $orderID, $cuponCodigo);
    
    if (!$stmtPed->execute()) throw new Exception("Error al guardar pedido: " . $stmtPed->error);
    $pedido_id = $conn->insert_id;


    // =========================================================================
    // PASO 6: DETALLES, STOCK Y CONSUMO DE CUPÓN
    // =========================================================================
    $stmtDet = $conn->prepare("INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    $stmtStk = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

    $listaProductosHTML = "";

    foreach ($itemsProcesados as $item) {
        // Detalle
        $stmtDet->bind_param("iiid", $pedido_id, $item['id'], $item['cantidad'], $item['precio']);
        $stmtDet->execute();

        // Restar Stock
        $stmtStk->bind_param("ii", $item['cantidad'], $item['id']);
        $stmtStk->execute();

        // HTML Correo
        $subtotalItem = number_format($item['precio'] * $item['cantidad'], 2);
        $listaProductosHTML .= "
            <tr style='border-bottom: 1px solid #eee;'>
                <td style='padding: 10px; color: #333;'>{$item['nombre']}</td>
                <td style='padding: 10px; text-align: center; color: #555;'>{$item['cantidad']}</td>
                <td style='padding: 10px; text-align: right; color: #333;'>$$subtotalItem</td>
            </tr>";
    }

    // Quemar Cupón
    if ($idCuponAplicado) {
        $stmtCupUpd = $conn->prepare("UPDATE cupones SET usos_actuales = usos_actuales + 1 WHERE id = ?");
        $stmtCupUpd->bind_param("i", $idCuponAplicado);
        $stmtCupUpd->execute();
    }

    $conn->commit();


    // =========================================================================
    // PASO 7: ENVIAR CORREO DE CONFIRMACIÓN
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
    registrarLog("ERROR FATAL: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error procesando el pedido.']);
}
?>