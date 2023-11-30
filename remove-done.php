<?php

use GuzzleHttp\Client;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Exception\RequestException;

require 'vendor/autoload.php';

$url = 'http://localhost:9091/transmission/rpc/';

$client = new Client([
    'base_uri' => $url,
    'headers' => [
        'Cache-Control' => 'no-cache',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
    ]
]);

try {


    $response = $client->get('', ['http_errors' => false]);
    $sessionId = $response->getHeaderLine('X-Transmission-Session-Id');

    $response = $client->post('', [
        'headers' => ['X-Transmission-Session-Id' => $sessionId],
        'json' => [
            'method' => 'torrent-get',
            'arguments' => [
                //'ids' => '',
                'fields' => [
                    'id',
                    'isFinished',
                    'name',
                ],
            ],
        ]
    ]);

    $torrents = json_decode($response->getBody(), false, 512, JSON_THROW_ON_ERROR);

    $toBeDeleted = [];

    foreach ($torrents->arguments->torrents as $torrent) {
        if($torrent->isFinished) {
            echo $torrent->id . ' ' . $torrent->name . PHP_EOL;
            $toBeDeleted[] = $torrent->id;
        }
    }

    $client->post('', [
        'headers' => ['X-Transmission-Session-Id' => $sessionId],
        'json' => [
            'method' => 'torrent-remove',
            'arguments' => [
                'ids' => $toBeDeleted,
            ],
        ]
    ]);

} catch (RequestException $e) {
    echo sprintf("failed [%s]\n", get_class($e));
    echo (new MessageFormatter(MessageFormatter::DEBUG))->format(
            $e->getRequest(),
            $e->getResponse(),
            $e
        ) . PHP_EOL;
} catch (\Throwable $e) {
    echo sprintf("failed [%s] %s\n", get_class($e), $e->getMessage());
}
