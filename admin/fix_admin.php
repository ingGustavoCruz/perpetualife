<?php
// fix_admin.php
require_once '../api/conexion.php';

// 1. Limpiamos la tabla
$conn->query("TRUNCATE TABLE kaiexper_perpetualife.usuarios_admin");

// 2. Generamos el hash real desde el motor de PHP
$pass_plana = "admin123";
$nuevo_hash = password_hash($pass_plana, PASSWORD_DEFAULT);

// 3. Insertamos
$stmt = $conn->prepare("INSERT INTO kaiexper_perpetualife.usuarios_admin (usuario, password, nombre) VALUES ('admin', ?, 'Gustavo Cruz')");
$stmt->bind_param("s", $nuevo_hash);

if($stmt->execute()){
    echo "✅ ¡LISTO! El hash real generado es: <br><code>" . $nuevo_hash . "</code>";
    echo "<br><br><b>Ya puedes ir al login y entrar con:</b><br>Usuario: admin<br>Password: admin123";
    echo "<br><br><a href='login.php'>Ir al Login</a>";
} else {
    echo "❌ Error: " . $conn->error;
}
?>