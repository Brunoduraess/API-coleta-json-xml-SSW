<?php

date_default_timezone_set('America/Bahia');

error_reporting(0);

// Defina o token de autorização esperado
$expectedToken = "Bearer e0c6fd31-b699-46ae-95cd-efebfcd78f55";

// Captura o cabeçalho "Authorization"
$headers = apache_request_headers();
$authHeader = $headers['Authorization'] ?? '';

if ($authHeader !== $expectedToken) {
    // Token inválido ou não fornecido
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Token de autorização inválido']);
    exit;
}

$hoje = date("Y-m-d H:i:s");

include('conexao.php');

include('function.php');

$dataColeta = validaDiaUtil($hoje);

// URL do WSDL e do serviço SOAP
$wsdl = 'https://ssw.inf.br/ws/sswCotacaoColeta/index.php?wsdl';
$serviceUrl = 'https://ssw.inf.br/ws/sswCotacaoColeta/index.php';

// Captura os dados JSON enviados na requisição POST
$jsonData = file_get_contents('php://input');

if (!$jsonData) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'No JSON input found in php://input']);
    exit;
}

$requestData = json_decode($jsonData, true);

$cotacao = $requestData['cotacao'];

$limiteColeta = $dataColeta . $requestData['limiteColeta'];

$observacao = "WPP: " . $contato . " | H.A: " . $almoco . " | " . $obs;

$buscaCotacao = "SELECT user, phone, token FROM api_cotacao WHERE cotacao = '$cotacao'";
$queryCotacao = $conexao->query($buscaCotacao);

if (mysqli_num_rows($queryCotacao) > 0) {

    $dadosCotacao = $queryCotacao->fetch_assoc();

    $nome = $dadosCotacao['user'];
    $contato = $dadosCotacao['phone'];
    $token = $dadosCotacao['token'];

    $almoco = $requestData['almoco'];

    $obs = $requestData['obs'];

    $observacao = "WPP: " . $contato . " | H.A: " . $almoco . " | " . $obs;


    // Verifica se o JSON foi recebido corretamente
    if ($requestData) {
        // Preenche o XML com os dados do JSON
        $soapRequest = <<<XML
            <soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:sswinfbr.sswCotacao">
            <soapenv:Header/>
            <soapenv:Body>
                <urn:coletar soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                    <dominio xsi:type="xsd:string">{$requestData['dominio']}</dominio>
                    <login xsi:type="xsd:string">{$requestData['login']}</login>
                    <senha xsi:type="xsd:string">{$requestData['senha']}</senha>
                    <cotacao xsi:type="xsd:integer">{$cotacao}</cotacao>
                    <limiteColeta xsi:type="xsd:datetime">{$limiteColeta}</limiteColeta>
                    <token xsi:type="xsd:string">{$token}</token>
                    <solicitante xsi:type="xsd:string">{$nome}</solicitante>
                    <observacao xsi:type="xsd:string">{$observacao}</observacao>
                    <chave_nfe xsi:type="xsd:string">{$requestData['chave_nfe']}</chave_nfe>
                    <nroPedido xsi:type="xsd:string">{$requestData['nroPedido']}</nroPedido>
                </urn:coletar>
            </soapenv:Body>
            </soapenv:Envelope>
            XML;

        // Função para realizar a solicitação SOAP e retornar o resultado como JSON
        function callSoapApi($wsdl, $serviceUrl, $soapRequest)
        {
            try {
                // Configurações do cliente SOAP
                $client = new SoapClient($wsdl, [
                    'trace' => 1, // Para depuração
                    'exceptions' => true,
                ]);

                // Faz a solicitação SOAP personalizada
                $response = $client->__doRequest($soapRequest, $serviceUrl, 'coletar', SOAP_1_1);

                // Converte a resposta XML para um objeto SimpleXMLElement
                $xml = simplexml_load_string($response);

                // Extrai o XML contido dentro do campo <return>
                $returnContent = (string) $xml->xpath('//return')[0];

                // Converte o conteúdo de <return> em um objeto SimpleXMLElement
                $innerXml = simplexml_load_string($returnContent);

                // Converte o objeto SimpleXMLElement para JSON
                $json = json_encode($innerXml);

                return $json;
            } catch (SoapFault $fault) {
                return json_encode(['error' => $fault->getMessage()]);
            }
        }

        // Define os cabeçalhos para permitir o acesso CORS e especificar o tipo de conteúdo como JSON
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/json');

        // Chama a função e exibe o resultado
        $responseJson = callSoapApi($wsdl, $serviceUrl, $soapRequest);

        $responseArray = json_decode($responseJson, true);

        // Adiciona o valor de $dataColeta ao array de resposta
        $responseArray['limiteColeta'] = $limiteColeta;

        // Reencoda o array de volta para JSON
        $responseJsonAtualizado = json_encode($responseArray, JSON_PRETTY_PRINT);


        if ($responseArray['erro'] == "0") {
            $status = "OK";
            $errorMessage = "";
        } else {
            $status = "ERRO";
            $errorMessage = $responseArray['mensagem'];
        }

        $grava_log = "INSERT INTO api_coleta(user, phone, request, response, status, error_message) VALUES (
        '" . $requestData['contact.name'] . "',
        '" . $requestData['contact.number'] . "',
        '" . $jsonData . "',
        '" . $responseJson . "',
        '" . $status . "', 
        '" . $errorMessage . "')";

        mysqli_query($conexao, $grava_log);

        echo $responseJsonAtualizado;

    } else {
        // Retorna um erro se os dados JSON não forem recebidos corretamente
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => 'Invalid JSON input']);
    }
} else {

    header('HTTP/1.1 400 Bad Request');

    $responseData['erro'] = 99;
    $responseData['mensagem'] = "Cotação não foi encontrada na base de dados.";

    $responseDataJson = json_encode($responseData, JSON_PRETTY_PRINT);

   echo $responseDataJson;
}
?>