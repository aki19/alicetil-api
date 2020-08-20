<?php

namespace App\Libraries;

class WebAPI {

    public static function direct_get($url, $parameters = array(), $json_decode = true, $assoc = false, $headers = array(), $proxys = array()) {

        $query = http_build_query($parameters);

        $url .= "?" . $query;

        $curl = curl_init($url);

        if (isset($proxys["proxy_server"]) && $proxys["proxy_server"] && isset($proxys["proxy_port"]) && $proxys["proxy_port"]) {
            curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, TRUE);
            curl_setopt($curl, CURLOPT_PROXY, "http://{$proxys["proxy_server"]}:{$proxys["proxy_port"]}");
            curl_setopt($curl, CURLOPT_PROXYPORT, $proxys["proxy_port"]);
        }

        if (count($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_COOKIEJAR, "cookie");
        curl_setopt($curl, CURLOPT_COOKIEFILE, "tmp");
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);

        if (strpos($url, "https") !== false) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            curl_setopt($curl, CURLOPT_SSL_CIPHER_LIST, "TLSv1");
        }

        $output = curl_exec($curl);
        $info   = curl_getinfo($curl);

        if ($json_decode) {
            return [$info["http_code"], json_decode($output, $assoc)];
        } else {
            return [$info["http_code"], $output];
        }

    }

}
