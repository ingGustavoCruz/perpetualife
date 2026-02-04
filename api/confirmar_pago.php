<?php
/**
 * api/confirmar_pago.php
 * Versión ROBUSTA (La tuya)
 */
header('Content-Type: application/json');
require_once 'conexion.php'; // Asegúrate que este archivo crea la variable $conn como new mysqli(...)

// 1. Recibimos los datos JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron datos']);
    exit;
}

$orderID = $input['orderID'];
$clienteData = $input['cliente']; // { nombre, email, telefono, direccion }
$cart = $input['cart'];
$total = $input['total'];

// 2. Iniciar Transacción
$conn->begin_transaction();

try {
    // ---------------------------------------------------------
    // PASO 1: GESTIONAR EL CLIENTE
    // ---------------------------------------------------------
    $cliente_id = 0;
    
    // Buscamos por email
    $stmtCheck = $conn->prepare("SELECT id FROM clientes WHERE email = ? LIMIT 1");
    $stmtCheck->bind_param("s", $clienteData['email']);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($resCheck->num_rows > 0) {
        // ACTUALIZAR EXISTENTE
        $row = $resCheck->fetch_assoc();
        $cliente_id = $row['id'];
        
        // Actualizamos incluyendo el TELEFONO
        $stmtUpdate = $conn->prepare("UPDATE clientes SET nombre = ?, direccion = ?, telefono = ? WHERE id = ?");
        $stmtUpdate->bind_param("sssi", $clienteData['nombre'], $clienteData['direccion'], $clienteData['telefono'], $cliente_id);
        $stmtUpdate->execute();
    } else {
        // CREAR NUEVO
        $stmtInsertCli = $conn->prepare("INSERT INTO clientes (nombre, email, telefono, direccion, fecha_registro) VALUES (?, ?, ?, ?, NOW())");
        $stmtInsertCli->bind_param("ssss", $clienteData['nombre'], $clienteData['email'], $clienteData['telefono'], $clienteData['direccion']);
        
        if (!$stmtInsertCli->execute()) {
            throw new Exception("Error al registrar cliente: " . $stmtInsertCli->error);
        }
        $cliente_id = $conn->insert_id;
    }

    // ---------------------------------------------------------
    // PASO 2: CREAR EL PEDIDO
    // ---------------------------------------------------------
    $estado = 'PAGADO';
    $moneda = 'MXN'; 
    
    $stmtPedido = $conn->prepare("INSERT INTO pedidos (cliente_id, paypal_order_id, total, moneda, estado, fecha) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmtPedido->bind_param("isdss", $cliente_id, $orderID, $total, $moneda, $estado);

    if (!$stmtPedido->execute()) {
        throw new Exception("Error al crear pedido: " . $stmtPedido->error);
    }
    $pedido_id = $conn->insert_id;

    // ---------------------------------------------------------
    // PASO 3: DETALLES Y STOCK
    // ---------------------------------------------------------
    $stmtDetalle = $conn->prepare("INSERT INTO detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    $stmtStock = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

    foreach ($cart as $item) {
        // Insertar Detalle
        $stmtDetalle->bind_param("iiid", $pedido_id, $item['id'], $item['qty'], $item['precio']);
        if (!$stmtDetalle->execute()) {
            throw new Exception("Error al insertar detalle producto ID: " . $item['id']);
        }

        // Descontar Stock
        $stmtStock->bind_param("ii", $item['qty'], $item['id']);
        $stmtStock->execute();
    }

    // Confirmar todo
    $conn->commit();
    echo json_encode(['status' => 'success', 'pedido_id' => $pedido_id]);

} catch (Exception $e) {
    // Si algo falla, deshacer cambios
    $conn->rollback();
    // Loguear el error real en el servidor (opcional)
    error_log($e->getMessage());
    // Responder al frontend
    echo json_encode(['status' => 'error', 'message' => 'Error al procesar: ' . $e->getMessage()]);
}
?>