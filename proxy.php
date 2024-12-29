<?php
// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Content-Type: application/json');

// Get parameters from the request
$celular = isset($_GET['celular']) ? $_GET['celular'] : '';
$profile = isset($_GET['profile']) ? $_GET['profile'] : '';

// Construct the URL
$url = "https://espiazap.pro/v5/concluido/?celular=" . urlencode($celular) . "&profile=" . urlencode($profile);

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Skip SSL verification (not recommended for production)
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Execute the request
$response = curl_exec($ch);

// Check for errors
if(curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
} else {
    // Return the response
    echo $response;
}

// Close cURL session
curl_close($ch);
?>
