<?php
// Debug informatie pagina
// Deze pagina toont belangrijke informatie over de applicatie

// Voorkom dat deze pagina wordt gecachet
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Zet alle PHP fouten aan
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Basis HTML structuur
echo "<!DOCTYPE html>";
echo "<html lang='nl'>";
echo "<head>";
echo "    <meta charset='UTF-8'>";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "    <title>Debug Informatie - Financieel Beheer</title>";
echo "    <style>";
echo "        body { font-family: Arial, sans-serif; padding: 20px; }";
echo "        h1 { color: #3B82F6; }";
echo "        h2 { color: #4B5563; margin-top: 30px; }";
echo "        pre { background: #f1f5f9; padding: 10px; overflow: auto; }";
echo "        .success { color: green; }";
echo "        .error { color: red; }";
echo "        table { border-collapse: collapse; width: 100%; }";
echo "        table, th, td { border: 1px solid #e5e7eb; }";
echo "        th, td { padding: 8px; text-align: left; }";
echo "        th { background-color: #f3f4f6; }";
echo "    </style>";
echo "</head>";
echo "<body>";
echo "<h1>Debug Informatie - Financieel Beheer</h1>";

// Functie om bestanden te checken
function checkFile($path) {
    if (file_exists($path)) {
        echo "<span class='success'>✓ Bestand bestaat: $path</span><br>";
        echo "Laatst gewijzigd: " . date("Y-m-d H:i:s", filemtime($path)) . "<br>";
        if (is_readable($path)) {
            echo "<span class='success'>✓ Bestand is leesbaar</span><br>";
        } else {
            echo "<span class='error'>✗ Bestand is NIET leesbaar</span><br>";
        }
    } else {
        echo "<span class='error'>✗ Bestand bestaat NIET: $path</span><br>";
    }
}

// Controleer PHP versie en belangrijke instellingen
echo "<h2>PHP Informatie</h2>";
echo "PHP Versie: " . phpversion() . "<br>";
echo "Display Errors: " . ini_get('display_errors') . "<br>";
echo "Error Reporting: " . ini_get('error_reporting') . "<br>";

// Controleer belangrijke bestanden
echo "<h2>Belangrijke Bestanden</h2>";
$baseDir = dirname(dirname(__FILE__));
checkFile("$baseDir/views/layouts/app_layout.php");
checkFile("$baseDir/views/layouts/app_wrapper.php");
checkFile("$baseDir/views/layouts/app.php");
checkFile("$baseDir/public/index.php");

// Laat de debug log zien als deze bestaat
echo "<h2>Debug Log</h2>";
$debugLogPath = __DIR__ . "/debug.log";
if (file_exists($debugLogPath)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($debugLogPath)) . "</pre>";
} else {
    echo "<span class='error'>Debug log bestand bestaat niet: $debugLogPath</span>";
}

// Controleer $_SERVER variabelen
echo "<h2>Server Variabelen</h2>";
echo "<table>";
echo "<tr><th>Variabele</th><th>Waarde</th></tr>";
$importantVars = ['REQUEST_URI', 'SCRIPT_NAME', 'SCRIPT_FILENAME', 'DOCUMENT_ROOT', 'HTTP_HOST', 'SERVER_SOFTWARE'];
foreach ($importantVars as $var) {
    echo "<tr><td>$var</td><td>" . (isset($_SERVER[$var]) ? htmlspecialchars($_SERVER[$var]) : 'Niet gezet') . "</td></tr>";
}
echo "</table>";

// Toon geïncludeerde bestanden
echo "<h2>Geïncludeerde Bestanden</h2>";
$includedFiles = get_included_files();
echo "<pre>";
foreach ($includedFiles as $file) {
    echo htmlspecialchars($file) . "\n";
}
echo "</pre>";

echo "</body>";
echo "</html>"; 