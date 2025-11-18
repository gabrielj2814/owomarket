<?php


namespace Src\Authentication\Infrastructure\Services;

use Illuminate\Support\Facades\Http;

abstract class BaseApiClient {
    protected function get(string $endpoint, array $headers = [], string $host=""): array {
        if($host==""){
            $host=config("app.url");
        }
        $url=$host.$endpoint;
        return Http::timeout(30)
                  ->retry(3, 100)
                  ->withHeaders(array_merge([
                  'Content-Type' => 'application/json',
                ],$headers))
                  ->get($url)
                  ->throw()
                  ->json();
    }

    protected function post(string $endpoint, array $data = [], array $headers = [],string $host=""): array {
        if($host==""){
            $host=config("app.url");
        }
        $url=$host.$endpoint;
        return Http::timeout(30)
                  ->retry(3, 100)
                  ->withHeaders(array_merge([
                  'Content-Type' => 'application/json',
                ],$headers))
                  ->post($url,$data)
                  ->throw()
                  ->json();
    }
}

?>
