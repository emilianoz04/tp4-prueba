<?php
// ===============================
// CONFIGURACIÓN
// ===============================
// Lee la URL de la API desde variables de entorno
$url = $_ENV["API_URL"] ?? getenv("API_URL");

if (!$url) {
    die("Error: no se definió API_URL en .env");
}

// ===============================
// FUNCIONES
// ===============================

// $tipo puede ser "ok" o "error", aplica el estilo CSS correspondiente
// htmlspecialchars evita inyección de HTML
function mostrarMensaje($tipo, $mensaje) {
    echo "<div class='msg {$tipo}'>" . htmlspecialchars($mensaje) . "</div>";
}

// Devuelve el número formateado con 2 decimales, coma decimal y punto de miles
// Ej: 1234.5 → "1.234,50"
function formatearPrecio($valor) {
    if (!is_numeric($valor)) return "N/A";
    return number_format($valor, 2, ',', '.');
}

// ===============================
// CONSUMO API
// ===============================
$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    $errorMsg = "Error en cURL: " . curl_error($ch);
    curl_close($ch);
} else {
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $errorMsg = null;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>API Dólar</title>
<link rel="stylesheet" href="CSS/api2.css">
<script src="js/chart.js"></script>
</head>

<body>
<div class="container">

<h1>API Cotización del Dólar</h1>
<a href="index.php">← Volver al menú</a>

<?php
if ($errorMsg) {
    mostrarMensaje("error", $errorMsg);
    exit;
}

if ($http_code == 200) {

    $data = json_decode($response, true);

    if (!is_array($data)) {
        mostrarMensaje("error", "Error al decodificar JSON");
        exit;
    }

    mostrarMensaje("ok", "Consulta exitosa");

    // Se limita a 10 resultados para no sobrecargar la vista
    $data = array_slice($data, 0, 10);

    // Array simplificado con solo los campos que necesita el gráfico
    $datos = [];

    foreach ($data as $item) {
        $datos[] = [
            "nombre" => $item['nombre'] ?? 'N/A',
            "compra" => $item['compra'] ?? 0,
            "venta"  => $item['venta']  ?? 0
        ];
    }
?>

<div class="filtros">
    <label><input type="checkbox" value="Blue" checked> Blue</label>
    <label><input type="checkbox" value="Oficial" checked> Oficial</label>
    <label><input type="checkbox" value="Bolsa" checked> Bolsa</label>
    <label><input type="checkbox" value="Contado con liquidación" checked> CCL</label>
    <label><input type="checkbox" value="Tarjeta" checked> Tarjeta</label>
    <label><input type="checkbox" value="Mayorista" checked> Mayorista</label>
    <label><input type="checkbox" value="Cripto" checked> Cripto</label>
</div>

<canvas id="graficoDolar"></canvas>

<h3>Cotizaciones disponibles</h3>

<?php
    foreach ($data as $item) {

        $nombre = htmlspecialchars($item['nombre'] ?? 'Sin nombre');
        $compra = $item['compra'] ?? null;
        $venta  = $item['venta']  ?? null;
        $fecha  = htmlspecialchars($item['fechaActualizacion'] ?? 'Sin fecha');

        echo "<div class='card'>";
        echo "<div class='card-header'>{$nombre}</div>";
        echo "<div class='card-body'>";
        echo "<div class='precio compra'>Compra: $ " . formatearPrecio($compra) . "</div>";
        echo "<div class='precio venta'>Venta: $ "   . formatearPrecio($venta)  . "</div>";
        echo "</div>";
        echo "<div class='card-footer'>Actualizado: {$fecha}</div>";
        echo "</div>";
    }

    // ===============================
    // GUARDAR TXT
    // ===============================

    if (!file_exists("Data")) {
      mkdir("Data", 0777, true);
    }

    $archivo = "Data/respuestas.txt";
    $lineas  = [];

    // Cada línea del archivo tiene el formato: nombre|compra|venta|fecha
    foreach ($data as $item) {
        $lineas[] = implode("|", [
            $item['nombre']             ?? '',
            $item['compra']             ?? '',
            $item['venta']              ?? '',
            $item['fechaActualizacion'] ?? ''
        ]);
    }

    file_put_contents($archivo, implode(PHP_EOL, $lineas));

    mostrarMensaje("ok", "Datos guardados correctamente");

} else {
    mostrarMensaje("error", "Error HTTP: $http_code");
}
?>

</div>

<script>
// Datos generados en PHP, pasados a JS para el gráfico
const datos = <?php echo json_encode($datos ?? []); ?>;

const ctx = document.getElementById('graficoDolar');
let chart; // Se guarda la instancia para poder destruirla antes de cada re-render

// Color fijo por tipo de dólar. Si no está en el mapa, usa gris como fallback
const colores = {
    "Blue":                    "#3b82f6",
    "Oficial":                 "#22c55e",
    "Bolsa":                   "#f59e0b",
    "Contado con liquidación": "#ef4444",
    "Tarjeta":                 "#a78bfa", 
    "Mayorista":               "#c7f522",
    "Cripto":                  "#f97316",
};

function crearDatasets(filtros) {
    return datos
        .filter(d => filtros.includes(d.nombre))
        .map(d => ({
            label:       d.nombre,
            data:        [d.compra, d.venta], // Eje X: [Compra, Venta]
            borderColor: colores[d.nombre] || "#ccc",
            tension:     0.3
        }));
}

function renderChart() {
    const activos = Array.from(
        document.querySelectorAll(".filtros input:checked")
    ).map(cb => cb.value);

    if (chart) chart.destroy();

    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels:   ['Compra', 'Venta'],
            datasets: crearDatasets(activos)
        },
        options: {
            plugins: {
                legend: { labels: { color: '#e5e5e5' } }
            },
            scales: {
                x: { ticks: { color: '#aaa' } },
                y: { ticks: { color: '#aaa' } }
            }
        }
    });
}

// Re-renderiza el gráfico cada vez que cambia un filtro
document.querySelectorAll(".filtros input")
    .forEach(cb => cb.addEventListener("change", renderChart));

renderChart();
</script>

</body>
</html>