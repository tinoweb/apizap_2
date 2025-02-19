# Solução de Integração com API WhatsApp

Este documento descreve a arquitetura e estratégias implementadas para criar um proxy seguro e eficiente para consulta de perfis do WhatsApp.

## 🏗️ Arquitetura da Solução

### Componentes Principais
1. **Frontend (index.html)**
   - Interface do usuário em HTML/CSS/JavaScript
   - Bootstrap para estilização
   - Validação de entrada do usuário
   - Exibição dos dados do perfil

2. **Backend Proxy (api.php)**
   - Intermediário entre frontend e API externa
   - Gerenciamento de sessão e cookies
   - Sistema de retry para requisições
   - Tratamento de erros e timeouts

3. **API Externa**
   - Endpoint: aplicativoespiaozap.com
   - Autenticação baseada em cookies
   - Retorna dados de perfil do WhatsApp

## 🛠️ Estratégias Implementadas

### 1. Contorno de Restrições CORS
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
```
- Implementação de headers CORS para permitir requisições cross-origin
- Suporte ao preflight OPTIONS
- Gerenciamento de tipos de conteúdo

### 2. Autenticação em Duas Etapas
```php
// Primeira requisição: obter cookies
curl_setopt_array($ch, [
    CURLOPT_COOKIEJAR => $cookieJar,
    CURLOPT_COOKIEFILE => $cookieJar
]);

// Segunda requisição: usar cookies obtidos
curl_setopt_array($ch, [
    CURLOPT_COOKIEFILE => $cookieJar
]);
```
- Primeira requisição para obter cookies de autenticação
- Segunda requisição para acessar a API com os cookies
- Gerenciamento automático de sessão

### 3. Sistema de Retry com Backoff Exponencial
```php
function makeRequest($ch, $maxRetries = 3) {
    $attempt = 0;
    while ($attempt < $maxRetries) {
        if ($attempt > 0) {
            sleep($attempt); // Espera progressiva
        }
        $response = curl_exec($ch);
        if (!curl_errno($ch)) {
            return $response;
        }
        $attempt++;
    }
}
```
- Máximo de 3 tentativas por requisição
- Espera progressiva entre tentativas
- Registro de erros detalhado

### 4. Otimizações de Performance
```php
curl_setopt_array($ch, [
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TCP_KEEPALIVE => 1,
    CURLOPT_TCP_KEEPIDLE => 120,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
]);
```
- Timeouts configuráveis
- Conexões TCP persistentes
- Resolução IPv4 forçada
- Compressão de conteúdo (gzip, deflate)

### 5. Headers Específicos
```php
$headers = [
    'Content-Type: application/json',
    'Accept: */*',
    'Origin: https://aplicativoespiaozap.com',
    'Referer: https://aplicativoespiaozap.com/',
    'User-Agent: Mozilla/5.0...',
    'X-Requested-With: XMLHttpRequest'
];
```
- Simulação de requisição do navegador
- Headers de segurança
- Controle de cache

## 🔒 Segurança

1. **Validação de Entrada**
   - Sanitização de números de telefone
   - Validação de formato
   - Prevenção de injeção

2. **Proteção de Dados**
   - Sem armazenamento de dados sensíveis
   - Limpeza automática de arquivos temporários
   - Timeouts para prevenir ataques DoS

## 📊 Tratamento de Erros

1. **Níveis de Erro**
   - Erros de validação (400)
   - Erros de autenticação (401/403)
   - Erros de timeout (408)
   - Erros internos (500)

2. **Resposta de Erro Estruturada**
```json
{
    "status": 500,
    "message": "Erro ao consultar a API",
    "error": "Detalhes técnicos",
    "info": { "debug_info": "..." }
}
```

## 🚀 Como Usar

1. **Requisição**
```javascript
fetch('api.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ phone: "5511999999999" })
});
```

2. **Resposta Bem-Sucedida**
```json
{
    "number": "5511999999999",
    "profilePic": "URL_DA_FOTO",
    "about": "Status do usuário",
    "isWAContact": true,
    "isBusiness": false,
    "date": "2025-02-19T15:54:50.956Z"
}
```

## 📝 Lições Aprendidas

1. **Gestão de Cookies**
   - Essencial para APIs que dependem de sessão
   - Necessidade de limpar cookies temporários

2. **Retry Strategy**
   - Backoff exponencial reduz sobrecarga
   - Logging detalhado auxilia no debug

3. **Headers Corretos**
   - Fundamentais para bypass de proteções
   - Simulação precisa de browser

4. **Performance**
   - Timeouts adequados são cruciais
   - Conexões persistentes melhoram resposta

## 🔄 Possíveis Melhorias

1. **Cache**
   - Implementar cache de respostas
   - Reduzir carga no servidor externo

2. **Rate Limiting**
   - Proteção contra abuso
   - Fila de requisições

3. **Monitoramento**
   - Logging de sucesso/erro
   - Métricas de performance

4. **Escalabilidade**
   - Distribuição de carga
   - Pooling de conexões

## ⚠️ Considerações Importantes

1. Respeitar limites da API externa
2. Manter headers atualizados
3. Monitorar mudanças na API
4. Backup de configurações
5. Documentar alterações

Este documento serve como referência para implementações similares e pode ser adaptado para casos mais complexos.
