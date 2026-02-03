<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$json = file_get_contents('php://input');
$datos = json_decode($json, true);

if (!$datos || !isset($datos['cliente'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
    exit;
}

$orderID = $datos['orderID'];
$cart    = $datos['cart'];
$total   = $datos['total'];
$c       = $datos['cliente']; // Datos del formulario

$conn->begin_transaction();

try {
    // 1. Insertar o identificar al cliente
    // Usamos el email como identificador para no duplicar clientes
    $stmt_c = $conn->prepare("INSERT INTO kaiexper_perpetualife.clientes (nombre, email, direccion) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), direccion=VALUES(direccion)");
    $stmt_c->bind_param("sss", $c['nombre'], $c['email'], $c['direccion']);
    $stmt_c->execute();
    $cliente_id = $conn->insert_id;

    // 2. Insertar el pedido vinculado al cliente
    $stmt_p = $conn->prepare("INSERT INTO kaiexper_perpetualife.pedidos (cliente_id, paypal_order_id, total, moneda, estado) VALUES (?, ?, ?, 'MXN', 'COMPLETADO')");
    $stmt_p->bind_param("isd", $cliente_id, $orderID, $total);
    $stmt_p->execute();
    $pedido_id = $conn->insert_id;

    // 3. Detalles y Stock (Tu lógica original optimizada)
    $stmt_detalle = $conn->prepare("INSERT INTO kaiexper_perpetualife.detalles_pedido (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    $stmt_stock = $conn->prepare("UPDATE kaiexper_perpetualife.productos SET stock = stock - ? WHERE id = ?");

    foreach ($cart as $item) {
        $stmt_detalle->bind_param("iiid", $pedido_id, $item['id'], $item['qty'], $item['precio']);
        $stmt_detalle->execute();

        $stmt_stock->bind_param("ii", $item['qty'], $item['id']);
        $stmt_stock->execute();
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'pedido_id' => $pedido_id]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}