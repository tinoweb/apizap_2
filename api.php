<?php
// Configurações de cabeçalho
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Max-Age: 3600');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Função para validar o número do WhatsApp
function validateWhatsAppNumber($number) {
    // Remove caracteres não numéricos
    $number = preg_replace('/[^0-9]/', '', $number);
    
    // Verifica se o número tem entre 10 e 13 dígitos
    if (strlen($number) < 10 || strlen($number) > 13) {
        return false;
    }
    
    return $number;
}

// Verifica o método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 405,
        'message' => 'Método não permitido. Use POST.'
    ]);
    exit;
}

// Obtém o corpo da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verifica se o parâmetro phone foi fornecido
if (!isset($data['phone'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 400,
        'message' => 'Parâmetro "phone" é obrigatório'
    ]);
    exit;
}

// Valida o número
$numero = validateWhatsAppNumber($data['phone']);
if (!$numero) {
    http_response_code(400);
    echo json_encode([
        'status' => 400,
        'message' => 'Número de WhatsApp inválido'
    ]);
    exit;
}

// Configuração da requisição para a API externa
$mainUrl = "https://aplicativoespiaozap.com/";
$apiUrl = "https://aplicativoespiaozap.com/assets/php/fetch_img.php";

// Função para fazer a requisição com retry
function makeRequest($ch, $maxRetries = 3) {
    $attempt = 0;
    $lastError = '';
    $lastInfo = [];
    
    while ($attempt < $maxRetries) {
        $response = curl_exec($ch);
        
        if (!curl_errno($ch)) {
            return $response;
        }
        
        $lastError = curl_error($ch);
        $lastInfo = curl_getinfo($ch);
        $attempt++;
        
        if ($attempt < $maxRetries) {
            // Espera progressiva entre tentativas (1s, 2s, 3s...)
            sleep($attempt);
        }
    }
    
    return [
        'error' => $lastError,
        'info' => $lastInfo,
        'failed' => true
    ];
}

// Inicializa o cURL e mantém os cookies
$ch = curl_init();
$cookieJar = tempnam(sys_get_temp_dir(), 'cookie');

// Primeira requisição para obter cookies
curl_setopt_array($ch, [
    CURLOPT_URL => $mainUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookieJar,
    CURLOPT_COOKIEFILE => $cookieJar,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_ENCODING => 'gzip, deflate',
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_TCP_KEEPALIVE => 1,
    CURLOPT_TCP_KEEPIDLE => 120,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_HTTPHEADER => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: pt-BR,pt;q=0.9,es;q=0.8',
        'Accept-Encoding: gzip, deflate',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36',
        'sec-ch-ua: "Not(A:Brand";v="99", "Google Chrome";v="133", "Chromium";v="133"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"'
    ]
]);

// Executa a primeira requisição para obter cookies
$response = makeRequest($ch);
if (is_array($response) && isset($response['failed'])) {
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Erro ao conectar com o site',
        'error' => $response['error'],
        'info' => $response['info']
    ]);
    exit;
}

// Aguarda um pouco para simular comportamento humano
usleep(500000); // 500ms

// Segunda requisição para a API com os cookies obtidos
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['phone' => $numero]),
    CURLOPT_ENCODING => 'gzip, deflate',
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TCP_KEEPALIVE => 1,
    CURLOPT_TCP_KEEPIDLE => 120,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: */*',
        'Accept-Language: pt-BR,pt;q=0.9,es;q=0.8',
        'Accept-Encoding: gzip, deflate',
        'Origin: https://aplicativoespiaozap.com',
        'Referer: https://aplicativoespiaozap.com/',
        'sec-ch-ua: "Not(A:Brand";v="99", "Google Chrome";v="133", "Chromium";v="133"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
        'sec-fetch-dest: empty',
        'sec-fetch-mode: cors',
        'sec-fetch-site: same-origin',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36',
        'X-Requested-With: XMLHttpRequest'
    ]
]);

// Executa a requisição para a API com retry
$apiResponse = makeRequest($ch);

// Verifica se houve erro no retry
if (is_array($apiResponse) && isset($apiResponse['failed'])) {
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Erro ao consultar a API',
        'error' => $apiResponse['error'],
        'info' => $apiResponse['info']
    ]);
    exit;
}

// Se a resposta for bem-sucedida, tenta decodificar
$decodedResponse = json_decode($apiResponse, true);
if ($decodedResponse !== null) {
    echo json_encode($decodedResponse);
} else {
    echo $apiResponse;
}

// Fecha a conexão cURL
curl_close($ch);

// Remove o arquivo de cookie temporário
@unlink($cookieJar);
