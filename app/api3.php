<?php
// ===============================
// CONFIGURACIÓN
// ===============================
$url = $_ENV["API_URL"] ?? getenv("API_URL");

if (!$url) {
    die("Error: no se definió API_URL en .env");
}

// ===============================
// FUNCIONES
// ===============================
function mostrarMensaje($tipo, $mensaje) {
    echo "<div class='msg $tipo'>";
    echo $mensaje;
    echo "</div>";
}

// ===============================
// CONSUMO API
// ===============================
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);

// Error cURL
if (curl_errno($ch)) {
    $error = curl_error($ch);
    curl_close($ch);
    $errorMsg = "Error en cURL: $error";
} else {

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    $body = substr($response, $header_size);

    curl_close($ch);

    $errorMsg = null;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>API Eventos Presidenciales</title>
    <link rel="stylesheet" href="CSS/api3.css">
</head>

<body>

<div class="container">

    <header class="header">
        <h1>Eventos Presidenciales</h1>
        <p>Consulta de eventos políticos históricos</p>
    </header>

    <a class="btn" href="index.php">← Volver al menú</a>

    <hr style="border:1px solid #1f1f2e; margin:20px 0;">

<?php
// ===============================
// ERROR cURL
// ===============================
if (isset($errorMsg)) {
    mostrarMensaje("error", $errorMsg);
    exit;
}

// ===============================
// RESPUESTA API
// ===============================
if ($http_code == 200) {

    $data = json_decode($body, true);

    if (!is_array($data)) {
        mostrarMensaje("error", "Error al decodificar JSON");
        exit;
    }

    mostrarMensaje("ok", "Consulta exitosa de eventos");

    echo "<div class='grid'>";

    foreach ($data as $evento) {

        $fecha  = htmlspecialchars($evento['fecha'] ?? 'Sin fecha');
        $tipo   = htmlspecialchars($evento['tipo'] ?? 'Sin tipo');
        $nombre = htmlspecialchars($evento['evento'] ?? 'Sin evento');

        echo "<div class='card'>";
        echo "<h2>$nombre</h2>";
        echo "<p><strong>Fecha:</strong> $fecha</p>";
        echo "<p><strong>Tipo:</strong> $tipo</p>";
        echo "</div>";
    }

    echo "</div>";

    // ===============================
    // GUARDADO EN ARCHIVO
    // ===============================
    if (!file_exists("Data")) {
        mkdir("Data", 0777, true);
    }

    $archivo = "Data/respuestas.txt";

    foreach ($data as $evento) {
        if (is_array($evento)) {
            $linea = implode("|", [
                $evento['fecha'] ?? '',
                $evento['tipo'] ?? '',
                $evento['evento'] ?? ''
            ]);

            file_put_contents($archivo, $linea . PHP_EOL, FILE_APPEND);
        }
    }

    mostrarMensaje("ok", "Datos guardados correctamente");

} elseif ($http_code >= 400 && $http_code < 500) {
    mostrarMensaje("warning", "Error cliente - Código $http_code");
} elseif ($http_code >= 500) {
    mostrarMensaje("error", "Error servidor - Código $http_code");
} else {
    mostrarMensaje("warning", "Respuesta inesperada - Código $http_code");
}
?>

</div>

</body>
</html>
