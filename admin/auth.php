<?php
// admin/auth.php (MODO DIAGNÓSTICO)
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../api/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['usuario'];
    $pass = $_POST['password'];

    echo "--- INICIO DE DIAGNÓSTICO ---<br>";
    echo "Intentando buscar usuario: " . htmlspecialchars($user) . "<br>";

    // Verificamos si la conexión existe
    if (!isset($conn)) {
        die("❌ ERROR: La variable de conexión \$conn no existe. Revisa '../api/conexion.php'");
    }

    $stmt = $conn->prepare("SELECT id, password, nombre FROM kaiexper_perpetualife.usuarios_admin WHERE usuario = ?");
    
    if (!$stmt) {
        die("❌ ERROR EN SQL: " . $conn->error);
    }

    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user_data = $result->fetch_assoc()) {
        echo "✅ Usuario encontrado en la BD.<br>";
        echo "Hash en BD: " . $user_data['password'] . "<br>";
        
        if (password_verify($pass, $user_data['password'])) {
            echo "✨ ¡CONTRASEÑA CORRECTA! Redirigiendo...";
            $_SESSION['admin_id'] = $user_data['id'];
            $_SESSION['admin_nombre'] = $user_data['nombre'];
            $conn->query("UPDATE kaiexper_perpetualife.usuarios_admin SET ultimo_login = NOW() WHERE id = " . $user_data['id']);
            header("Location: index.php");
            exit;
        } else {
            echo "❌ LA CONTRASEÑA NO COINCIDE.<br>";
            echo "Tu ingresaste: " . $pass;
        }
    } else {
        echo "❌ EL USUARIO NO EXISTE EN LA TABLA.";
    }
    echo "<br><br><a href='login.php'>Volver al login</a>";
    exit;
}