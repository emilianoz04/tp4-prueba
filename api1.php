<?php
// CONFIGURACIÓN
$url = $_ENV["API_URL"] ?? getenv("API_URL");

if (!$url) {
    die("Error: no se definió API_URL en .env");
}

// FUNCIONES
function mostrarMensaje($tipo, $mensaje) {
    echo "<div class='msg $tipo'>";
    echo $mensaje;
    echo "</div>";
}

// CONSUMO API
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
    <title>API Feriados</title>
    <link rel="stylesheet" href="CSS/api1.css">
</head>

<body>

<div class="container">

    <h3>API de Feriados</h3>

    <a href="index.php">← Volver al menú</a>

    <hr style="border:1px solid #1f1f2e; margin:20px 0;">

<?php
// SI HAY ERROR DE CURL
if (isset($errorMsg)) {
    mostrarMensaje("error", $errorMsg);
    exit;
}

// MANEJO DE RESPUESTA
if ($http_code == 200) {

    $data = json_decode($body, true);

    if (!$data) {
        mostrarMensaje("error", "Error al decodificar JSON");
        exit;
    }

    mostrarMensaje("ok", "Consulta exitosa de feriados");

    echo "<h3>Listado de feriados</h3>";
    echo "<ul>";

    foreach ($data as $item) {
        echo "<li>";
        echo $item['fecha'] . " - " . $item['nombre'] . " (" . $item['tipo'] . ")";
        echo "</li>";
    }

    $dir = __DIR__ . "/Data";  // carpeta dentro de app/

    if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
    }

    $archivo = $dir . "/respuestas.txt";

    foreach ($data as $item) {
        if (is_array($item)) {
            file_put_contents($archivo, implode("|", $item) . PHP_EOL, FILE_APPEND);
        }
    }

    mostrarMensaje("ok", "Datos guardados correctamente ");

} elseif ($http_code >= 400 && $http_code < 500) {
    mostrarMensaje("warning", "Error en la solicitud (cliente) - Código $http_code");
} elseif ($http_code >= 500) {
    mostrarMensaje("error", "Error en el servidor - Código $http_code");
} else {
    mostrarMensaje("warning", "Respuesta inesperada: $http_code");
}
?>

</div>

</body>
</html>