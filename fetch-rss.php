<?php

use GuzzleHttp\Client;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Exception\RequestException;

require 'vendor/autoload.php';

// put your feeds here
$urls = [
    'https://distrowatch.com/news/torrents.xml',
];

// %s will be replaced with torrent file path
$command = 'transmission-remote -n \'transmission:transmission\' -a %s';

$cacheFile = __DIR__ . '/downloaded.json';
$temporaryTorrentFile = __DIR__ . '/temp.torrent';

// no need to edit below here

try {

    $downloaded = [];

    if (file_exists($cacheFile)) {
        $downloaded = json_decode(file_get_contents($cacheFile), false, 512, JSON_THROW_ON_ERROR);
    } elseif (!touch($cacheFile)) {
        echo 'Unable to create cache file!' . PHP_EOL;
        echo 'Create this manually with' . PHP_EOL . PHP_EOL;
        echo 'echo "[]" > ' . $cacheFile . PHP_EOL . PHP_EOL;
        echo 'And then grant it required permissions';
        die(1);
    }

    $client = new Client([
        'headers' => [
            'Cache-Control' => 'no-cache',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
        ]
    ]);

    foreach ($urls as $i => $url) {
        try {
            $rss = $client->get($url);

            $movies = new SimpleXMLElement($rss->getBody());

            foreach ($movies->channel->item as $j => $item) {

                $link = (string)$item->link;

                try {
                    if (in_array($link, $downloaded, true)) {
                        continue;
                    }

                    if (!filter_var($link, FILTER_VALIDATE_URL)) {
                        echo 'link malformed' . PHP_EOL;
                        continue;
                    }

                    $client->get($link, ['sink' => $temporaryTorrentFile]);

                    system(sprintf($command, $temporaryTorrentFile));

                    unlink($temporaryTorrentFile);

                    $downloaded[] = $link;
                } catch (RequestException $e) {
                    echo sprintf("url %s link %s failed [%s]\n", $i, $j, get_class($e));
                    echo (new MessageFormatter(MessageFormatter::DEBUG))->format(
                            $e->getRequest(),
                            $e->getResponse(),
                            $e
                        ) . PHP_EOL;
                } catch (\Throwable $e) {
                    echo sprintf("url %s link %s failed [%s] %s\n", $i, $j, get_class($e), $e->getMessage());
                }

            }
        } catch (RequestException $e) {
            echo sprintf("url %s failed [%s]\n", $i, get_class($e));
            echo (new MessageFormatter(MessageFormatter::DEBUG))->format(
                    $e->getRequest(),
                    $e->getResponse(),
                    $e
                ) . PHP_EOL;
        } catch (\Throwable $e) {
            echo sprintf("url %s failed [%s] %s\n", $i, get_class($e), $e->getMessage());
        }
    }

    file_put_contents($cacheFile, json_encode($downloaded, JSON_THROW_ON_ERROR));

} catch (\Throwable $e) {
    echo sprintf("failed [%s] %s\n", get_class($e), $e->getMessage());
    die(1);
}
