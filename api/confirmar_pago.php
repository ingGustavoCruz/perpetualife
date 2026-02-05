<?php
/**
 * api/confirmar_pago.php
 * VERSIÓN BLINDADA: Recálculo de totales, validación de cupones server-side y control de stock.
 */
require_once 'conexion.php';
require_once 'mailer.php'; // Asegúrate de que este archivo existe y funciona como ya lo tienes

header('Content-Type: application/json');

function registrarLog($mensaje) {
    file_put_contents('log_errores.txt', date('Y-m-d H:i:s') . " - " . $mensaje . "\n", FILE_APPEND);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    registrarLog("Error: No llegaron datos JSON.");
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron datos']);
    exit;
}

// Datos recibidos del Frontend
$orderID = $data['orderID'] ?? 'MANUAL';
$cart = $data['cart'] ?? [];
$cliente = $data['cliente'] ?? [];
$crearCuenta = $data['crear_cuenta'] ?? false;
$newPassword = $data['new_password'] ?? '';
$cuponCodigo = isset($data['cupon']) ? strtoupper(trim($conn->real_escape_string($data['cupon']))) : null;

registrarLog("Iniciando proceso para Cliente: " . ($cliente['email'] ?? 'Desconocido'));

$conn->begin_transaction();

try {
    // --- 1. SEGURIDAD: RECÁLCULO DE TOTALES ---
    $subtotalReal = 0;
    $itemsProcesados = []; // Aquí guardaremos los datos reales de la BD para usarlos después

    foreach ($cart as $item) {
        $idProd = intval($item['id']);
        $qty = intval($item['qty']);

        // Consultamos precio y stock REAL en la base de datos
        $stmtProd = $conn->prepare("SELECT id, nombre, precio, stock FROM kaiexper_perpetualife.productos WHERE id = ?");
        $stmtProd->bind_param("i", $idProd);
        $stmtProd->execute();
        $resProd = $stmtProd->get_result();
        
        if ($prodBD = $resProd->fetch_assoc()) {
            // Validar Stock
            if ($prodBD['stock'] < $qty) {
                throw new Exception("Stock insuficiente para el producto: " . $prodBD['nombre']);
            }

            $precioReal = floatval($prodBD['precio']);
            $subtotalReal += ($precioReal * $qty);

            // Guardamos para usar más tarde (sin confiar en el frontend)
            $itemsProcesados[] = [
                'id' => $prodBD['id'],
                'nombre' => $prodBD['nombre'],
                'precio' => $precioReal,
                'cantidad' => $qty
            ];
        } else {
            throw new Exception("Producto ID $idProd no encontrado en la base de datos.");
        }
        $stmtProd->close();
    }

    // --- 2. SEGURIDAD: VALIDACIÓN Y APLICACIÓN DE CUPÓN ---
    $descuentoReal = 0;
    $idCuponAplicado = null;
    $infoCupon = "Ninguno";

    if ($cuponCodigo) {
        $sqlCup = "SELECT * FROM kaiexper_perpetualife.cupones WHERE codigo = '$cuponCodigo' LIMIT 1";
        $resCup = $conn->query($sqlCup);

        if ($resCup && $rowCup = $resCup->fetch_assoc()) {
            $hoy = date('Y-m-d');
            
            // Validaciones estrictas del servidor
            $esActivo = $rowCup['estado_manual'] === 'activo';
            $esVigente = $rowCup['fecha_vencimiento'] >= $hoy;
            $hayStock = ($rowCup['limite_uso'] == 0) || ($rowCup['usos_actuales'] < $rowCup['limite_uso']);

            if ($esActivo && $esVigente && $hayStock) {
                $idCuponAplicado = $rowCup['id'];
                $tipoOferta = $rowCup['tipo_oferta'];
                $infoCupon = $cuponCodigo;

                // Calcular descuento monetario
                if ($tipoOferta === 'descuento' || $tipoOferta === 'ambos') {
                    if ($rowCup['tipo'] === 'porcentaje') {
                        $descuentoReal = $subtotalReal * ($rowCup['descuento'] / 100);
                    } else {
                        $descuentoReal = floatval($rowCup['descuento']);
                    }
                }
                // Nota: Si es solo "envio", el descuento monetario es 0, pero ya asumimos envío gratis en la lógica de negocio.
            } else {
                registrarLog("Cupón inválido o expirado intentado: $cuponCodigo");
            }
        }
    }

    // Total final blindado
    $totalCalculado = $subtotalReal - $descuentoReal;
    if ($totalCalculado < 0) $totalCalculado = 0;

    registrarLog("Subtotal Real: $subtotalReal | Descuento: $descuentoReal | Total Final: $totalCalculado");


    // --- 3. GESTIÓN DE CLIENTE ---
    $email = $conn->real_escape_string($cliente['email']);
    $nombre = $conn->real_escape_string($cliente['nombre']);
    $telefono = $conn->real_escape_string($cliente['telefono']);
    $direccion = $conn->real_escape_string($cliente['direccion']);
    $estadoCliente = $conn->real_escape_string($cliente['estado'] ?? '');
    
    $check = $conn->query("SELECT id FROM kaiexper_perpetualife.clientes WHERE email = '$email'");
    
    if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $cliente_id = $row['id'];
        
        if ($crearCuenta && !empty($newPassword)) {
            $passHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $conn->query("UPDATE kaiexper_perpetualife.clientes SET nombre='$nombre', telefono='$telefono', estado='$estadoCliente', direccion='$direccion', password='$passHash' WHERE id=$cliente_id");
        } else {
            $conn->query("UPDATE kaiexper_perpetualife.clientes SET nombre='$nombre', telefono='$telefono', direccion='$direccion' WHERE id=$cliente_id");
        }
    } else {
        $passHash = NULL;
        if ($crearCuenta && !empty($newPassword)) {
            $passHash = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        $stmt = $conn->prepare("INSERT INTO kaiexper_perpetualife.clientes (nombre, email, telefono, direccion, estado, password, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $nombre, $email, $telefono, $direccion, $estadoCliente, $passHash);
        $stmt->execute();
        $cliente_id = $conn->insert_id;
    }

    // --- 4. INSERTAR PEDIDO ---
    $sql_pedido = "INSERT INTO kaiexper_perpetualife.pedidos 
                   (cliente_id, fecha, total, estado, metodo_pago, id_transaccion, paypal_order_id, moneda) 
                   VALUES (?, NOW(), ?, 'COMPLETADO', 'PayPal', ?, ?, 'MXN')";
                   
    $stmt = $conn->prepare($sql_pedido);
    // Usamos $totalCalculado en lugar de $data['total']
    $stmt->bind_param("idss", $cliente_id, $totalCalculado, $orderID, $orderID);
    
    if (!$stmt->execute()) throw new Exception("Error al guardar pedido: " . $stmt->error);
    
    $pedido_id = $conn->insert_id;

    // --- 5. DETALLES, STOCK Y CUPÓN ---
    $stmt_detalle = $conn->prepare("INSERT INTO kaiexper_perpetualife.detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    $stmt_stock = $conn->prepare("UPDATE kaiexper_perpetualife.productos SET stock = stock - ? WHERE id = ?");

    $listaProductosHTML = "";

    foreach ($itemsProcesados as $item) {
        // Insertar detalle
        $stmt_detalle->bind_param("iiid", $pedido_id, $item['id'], $item['cantidad'], $item['precio']);
        $stmt_detalle->execute();

        // Descontar stock
        $stmt_stock->bind_param("ii", $item['cantidad'], $item['id']);
        $stmt_stock->execute();

        // HTML para correo
        $subtotalItem = number_format($item['precio'] * $item['cantidad'], 2);
        $listaProductosHTML .= "
            <tr style='border-bottom: 1px solid #eee;'>
                <td style='padding: 10px; color: #333;'>{$item['nombre']}</td>
                <td style='padding: 10px; text-align: center; color: #555;'>{$item['cantidad']}</td>
                <td style='padding: 10px; text-align: right; color: #333;'>$$subtotalItem</td>
            </tr>";
    }

    // Quemar cupón (sumar uso)
    if ($idCuponAplicado) {
        $conn->query("UPDATE kaiexper_perpetualife.cupones SET usos_actuales = usos_actuales + 1 WHERE id = $idCuponAplicado");
        registrarLog("Cupón ID $idCuponAplicado usado en pedido #$pedido_id");
    }

    $conn->commit();
    registrarLog("Pedido #$pedido_id guardado exitosamente.");

    // --- 6. ENVIAR CORREO (Usando tu mailer.php existente) ---
    $asuntoCorreo = "Confirmación de Compra #PERP-" . str_pad($pedido_id, 5, "0", STR_PAD_LEFT);
    $totalFormateado = number_format($totalCalculado, 2);
    
    // Si hubo descuento, lo mostramos en el correo
    $filaDescuento = "";
    if ($descuentoReal > 0) {
        $filaDescuento = "
        <tr>
            <td colspan='2' style='padding: 5px 10px; text-align: right; color: #ef4444; font-size: 12px;'>Descuento ($infoCupon):</td>
            <td style='padding: 5px 10px; text-align: right; color: #ef4444; font-size: 12px;'>-$$" . number_format($descuentoReal, 2) . "</td>
        </tr>";
    }

    $htmlCorreo = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden;'>
        <div style='background-color: #1e3a8a; color: white; padding: 20px; text-align: center;'>
            <h1 style='margin: 0; font-size: 24px;'>¡Gracias por tu compra!</h1>
            <p style='margin: 5px 0 0;'>Orden #$pedido_id</p>
        </div>
        <div style='padding: 20px;'>
            <p>Hola <strong>$nombre</strong>,</p>
            <p>Hemos recibido tu pago correctamente. Aquí tienes el resumen:</p>
            <table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>
                <thead>
                    <tr style='background-color: #f9fafb; text-align: left;'>
                        <th style='padding: 10px; color: #555;'>Producto</th>
                        <th style='padding: 10px; text-align: center; color: #555;'>Cant.</th>
                        <th style='padding: 10px; text-align: right; color: #555;'>Precio</th>
                    </tr>
                </thead>
                <tbody>$listaProductosHTML</tbody>
                <tfoot>
                    $filaDescuento
                    <tr>
                        <td colspan='2' style='padding: 15px; text-align: right; font-weight: bold; border-top: 2px solid #eee;'>TOTAL PAGADO:</td>
                        <td style='padding: 15px; text-align: right; font-weight: bold; color: #1e3a8a; font-size: 18px; border-top: 2px solid #eee;'>$$totalFormateado MXN</td>
                    </tr>
                </tfoot>
            </table>
            <div style='background-color: #f0f9ff; padding: 15px; border-radius: 8px; margin-top: 20px;'>
                <p style='margin: 0; font-weight: bold; color: #1e3a8a;'>Dirección de Envío:</p>
                <p style='margin: 5px 0 0; color: #555;'>$direccion</p>
            </div>
        </div>
    </div>";

    if(function_exists('enviarCorreo')) {
        if(enviarCorreo($email, $asuntoCorreo, $htmlCorreo)) {
            registrarLog("Correo enviado a $email");
        } else {
            registrarLog("FALLO envío de correo (función retornó false)");
        }
    } else {
        // Fallback por si mailer.php no tiene la función (aunque debería)
        registrarLog("Advertencia: Función enviarCorreo no encontrada.");
    }

    echo json_encode(['status' => 'success', 'pedido_id' => $pedido_id]);

} catch (Exception $e) {
    $conn->rollback();
    registrarLog("FATAL ERROR: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>