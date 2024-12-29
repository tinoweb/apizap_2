<?php
// Configurações de cabeçalho
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

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
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 405,
        'message' => 'Método não permitido. Use GET.'
    ]);
    exit;
}

// Verifica se o parâmetro número foi fornecido
if (!isset($_GET['numero'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 400,
        'message' => 'Parâmetro "numero" é obrigatório'
    ]);
    exit;
}

// Valida o número
$numero = validateWhatsAppNumber($_GET['numero']);
if (!$numero) {
    http_response_code(400);
    echo json_encode([
        'status' => 400,
        'message' => 'Número de WhatsApp inválido'
    ]);
    exit;
}

// Configuração da requisição para a API externa
$url = "https://espiazap.live/api/get-profile?username=" . urlencode($numero);

// Inicializa o cURL
$ch = curl_init();

// Configura as opções do cURL
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
]);

// Executa a requisição
$response = curl_exec($ch);

// Verifica se houve erro no cURL
if (curl_errno($ch)) {
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Erro ao consultar a API',
        'error' => curl_error($ch)
    ]);
    exit;
}

// Obtém o código HTTP
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Fecha a conexão cURL
curl_close($ch);

// Decodifica a resposta
$decodedResponse = json_decode($response, true);

// Verifica se a decodificação foi bem sucedida
if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'status' => 500,
        'message' => 'Erro ao processar resposta da API',
        'error' => json_last_error_msg()
    ]);
    exit;
}

// Retorna a resposta formatada
echo json_encode([
    'status' => $httpCode,
    'data' => $decodedResponse
]);
