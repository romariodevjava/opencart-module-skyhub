<?php


class SkyHubCommons {

    private const skyhubUrl = 'https://api.skyhub.com.br/';
    const REQUEST_METHOD_GET = 'GET';
    const REQUEST_METHOD_POST = 'POST';
    const REQUEST_METHOD_DELETE = 'DELETE';
    const REQUEST_METHOD_PUT = 'PUT';

    public static function executeRequest($emailSkyhub, $tokenSkyhub, $path, $request_method, $data = []) {
        $ch = curl_init(self::skyhubUrl . $path);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (in_array($request_method, [self::REQUEST_METHOD_POST, self::REQUEST_METHOD_PUT])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_method);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
        curl_setopt ($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-User-Email: ' . $emailSkyhub,
            'X-Api-Key: ' . $tokenSkyhub,
            'Content-Type: application/json',
        ));

        $result = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result === false || $status_code > 399) {
            throw new Exception('Erro ao fazer a requisição. Mais detalhes: ' . var_export($result));
        }

        return json_decode($result);
    }
}