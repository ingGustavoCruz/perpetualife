<?php
/**
 * api/confirmar_pago.php
 * CORREGIDO: Moneda MXN, Estatus 'COMPLETADO' y IDs de PayPal duplicados para compatibilidad.
 */
require_once 'conexion.php';
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

$orderID = $data['orderID'] ?? '';
$cart = $data['cart'] ?? [];
$total = $data['total'] ?? 0;
$cliente = $data['cliente'] ?? [];
$crearCuenta = $data['crear_cuenta'] ?? false;
$newPassword = $data['new_password'] ?? '';
$cuponCodigo = $data['cupon'] ?? null; 

registrarLog("Iniciando proceso para Cliente: " . ($cliente['email'] ?? 'Desconocido'));

$conn->begin_transaction();

try {
    // 1. GESTIÓN DE CLIENTE
    $email = $conn->real_escape_string($cliente['email']);
    $nombre = $conn->real_escape_string($cliente['nombre']);
    $telefono = $conn->real_escape_string($cliente['telefono']);
    $direccion = $conn->real_escape_string($cliente['direccion']);
    
    $check = $conn->query("SELECT id FROM kaiexper_perpetualife.clientes WHERE email = '$email'");
    
    if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $cliente_id = $row['id'];
        registrarLog("Cliente existente ID: " . $cliente_id);
        
        if ($crearCuenta && !empty($newPassword)) {
            $passHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $conn->query("UPDATE kaiexper_perpetualife.clientes SET nombre='$nombre', telefono='$telefono', direccion='$direccion', password='$passHash' WHERE id=$cliente_id");
        } else {
            $conn->query("UPDATE kaiexper_perpetualife.clientes SET nombre='$nombre', telefono='$telefono', direccion='$direccion' WHERE id=$cliente_id");
        }
    } else {
        registrarLog("Creando cliente nuevo...");
        $passHash = NULL;
        if ($crearCuenta && !empty($newPassword)) {
            $passHash = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        $stmt = $conn->prepare("INSERT INTO kaiexper_perpetualife.clientes (nombre, email, telefono, direccion, password, fecha_registro) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $nombre, $email, $telefono, $direccion, $passHash);
        $stmt->execute();
        $cliente_id = $conn->insert_id;
        registrarLog("Cliente creado ID: " . $cliente_id);
    }

    // 2. INSERTAR PEDIDO (CORREGIDO AQUI)
    registrarLog("Insertando pedido...");
    
    // CAMBIOS APLICADOS:
    // - Agregamos 'paypal_order_id' y 'moneda' a las columnas.
    // - 'estado' ahora es 'COMPLETADO' (string) en lugar de 1.
    // - 'moneda' ahora es 'MXN'.
    $sql_pedido = "INSERT INTO kaiexper_perpetualife.pedidos 
                   (cliente_id, fecha, total, estado, metodo_pago, id_transaccion, paypal_order_id, moneda) 
                   VALUES (?, NOW(), ?, 'COMPLETADO', 'PayPal', ?, ?, 'MXN')";
                   
    $stmt = $conn->prepare($sql_pedido);
    if(!$stmt) throw new Exception("Error preparando INSERT pedido: " . $conn->error);
    
    // Bind: cliente_id(i), total(d), orderID(s), orderID(s)
    // Pasamos $orderID dos veces para llenar ambas columnas
    $stmt->bind_param("idss", $cliente_id, $total, $orderID, $orderID);
    
    if (!$stmt->execute()) throw new Exception("Error ejecutando INSERT pedido: " . $stmt->error);
    
    $pedido_id = $conn->insert_id;
    registrarLog("Pedido creado ID: " . $pedido_id);

    // 3. DETALLES DEL PEDIDO
    $sql_detalle = "INSERT INTO kaiexper_perpetualife.detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
    $stmt_detalle = $conn->prepare($sql_detalle);
    $stmt_stock = $conn->prepare("UPDATE kaiexper_perpetualife.productos SET stock = stock - ? WHERE id = ?");

    // Preparar HTML para correo
    $listaProductosHTML = "";

    foreach ($cart as $item) {
        $stmt_detalle->bind_param("iiid", $pedido_id, $item['id'], $item['qty'], $item['precio']);
        $stmt_detalle->execute();

        $stmt_stock->bind_param("ii", $item['qty'], $item['id']);
        $stmt_stock->execute();

        $subtotalItem = number_format($item['precio'] * $item['qty'], 2);
        $listaProductosHTML .= "
            <tr style='border-bottom: 1px solid #eee;'>
                <td style='padding: 10px;'>{$item['nombre']}</td>
                <td style='padding: 10px; text-align: center;'>{$item['qty']}</td>
                <td style='padding: 10px; text-align: right;'>$$subtotalItem</td>
            </tr>";
    }

    $conn->commit();
    registrarLog("¡ÉXITO! Transacción completada.");

    // 4. ENVIAR CORREO
    require_once 'mailer.php';
    $asuntoCorreo = "Confirmación de Compra #PERP-" . str_pad($pedido_id, 5, "0", STR_PAD_LEFT);
    $totalFormateado = number_format($total, 2);
    
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
                        <th style='padding: 10px;'>Producto</th>
                        <th style='padding: 10px; text-align: center;'>Cant.</th>
                        <th style='padding: 10px; text-align: right;'>Precio</th>
                    </tr>
                </thead>
                <tbody>$listaProductosHTML</tbody>
                <tfoot>
                    <tr>
                        <td colspan='2' style='padding: 15px; text-align: right; font-weight: bold;'>TOTAL:</td>
                        <td style='padding: 15px; text-align: right; font-weight: bold; color: #1e3a8a; font-size: 18px;'>$$totalFormateado MXN</td>
                    </tr>
                </tfoot>
            </table>
            <div style='background-color: #f0f9ff; padding: 15px; border-radius: 8px; margin-top: 20px;'>
                <p style='margin: 0; font-weight: bold; color: #1e3a8a;'>Dirección de Envío:</p>
                <p style='margin: 5px 0 0; color: #555;'>$direccion</p>
            </div>
        </div>
    </div>";

    if(enviarCorreo($email, $asuntoCorreo, $htmlCorreo)) {
        registrarLog("Correo enviado a $email");
    } else {
        registrarLog("FALLO envío de correo");
    }

    echo json_encode(['status' => 'success', 'pedido_id' => $pedido_id]);

} catch (Exception $e) {
    $conn->rollback();
    registrarLog("FATAL ERROR: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>