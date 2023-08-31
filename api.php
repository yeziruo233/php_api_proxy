<?php
    function generate_requests_header() {
        return array(
            "Accept:" . $_SERVER["HTTP_ACCEPT"],
            "Accept-Encoding:" . $_SERVER["HTTP_ACCEPT_ENCODING|"],
            "Accept-Language:" . $_SERVER["HTTP_ACCEPT_LANGUAGE"],
            "Upgrade-Insecure-Requests:" . $_SERVER["HTTP_UPGRADE_INSECURE_REQUESTS"],
            "User-Agent:" . $_SERVER["HTTP_USER_AGENT"],
            "DNT:1",
            "sec-gpc:1",
        );
    }

    function header_to_array($res_header) {
        $headers = array();
        foreach (explode("\n", $res_header) as $i) {
            $kv = explode(":", $i);
            if (count($kv) == 2) {
                $headers[$kv[0]] = trim($kv[1]);
            }
        }
        return $headers;
    }

    function curl_requests($method, $url, $header, $data) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT,10);
        if ($header != null) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        if ($method == CURLOPT_POST) {
            curl_setopt($curl, CURLOPT_POST, true);
            if ($data != null) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        } else {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }

        $result = curl_exec($curl);
        $header_length = curl_getinfo($curl,CURLINFO_HEADER_SIZE);

        $result = array(
            "http_code" => curl_errno($curl) > 0? -1 : curl_getinfo($curl, CURLINFO_HTTP_CODE ),
            "err_code" => curl_errno($curl),
            "header" => curl_errno($curl) > 0? null : header_to_array(substr($result,0, $header_length)),
            "body" => curl_errno($curl) > 0? null : substr($result, $header_length),
        );

        curl_close($curl);
        return $result;
    }

    function set_response_header($code, $headers) {
        http_response_code($code);
        foreach ($headers as $k => $v) {
            header($k . ":" . $v);
        }
    }

    $requests_url = $_GET["url"];
    $token = $_GET["token"];

    if ($requests_url != "" && $token == "114514") {
        $client_header = generate_requests_header();
        $client_method = CURLOPT_HTTPGET;
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $client_method = CURLOPT_POST;
        }
        $result = curl_requests($client_method, $requests_url, $client_header, $_POST);
        if ($result["err_code"] > 0) {
            echo "error!";
            die(500);
        }
        set_response_header($result["http_code"], $result["header"]);
        echo $result["body"];
    } else {
        echo "error!";
    }

