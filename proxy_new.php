<?php
// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json');

// Get parameters from the request
$celular = isset($_GET['celular']) ? $_GET['celular'] : '';
$profile = isset($_GET['profile']) ? $_GET['profile'] : '';

// Construct the URL
$url = "https://espiazap.live/api/get-profile?username=" . urlencode($celular);

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

// Execute the request
$response = curl_exec($ch);

// Check for cURL errors
if(curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
    exit;
}

// Get HTTP status code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Try to decode the response
$decoded = json_decode($response, true); // Adicionado true para forçar array

if ($decoded !== null) {
    // Se a decodificação foi bem sucedida, retorna a resposta formatada
    echo json_encode([
        'status' => $httpCode,
        'data' => $decoded
    ]);
} else {
    // Se houver erro na decodificação, retorna erro
    echo json_encode([
        'status' => $httpCode,
        'error' => 'Invalid JSON response',
        'raw_response' => $response
    ]);
}
?>
