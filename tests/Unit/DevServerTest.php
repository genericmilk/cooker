<?php

namespace Genericmilk\Cooker\Tests\Unit;

use Genericmilk\Cooker\Build\DevServer;

test('DevServer accepts an SSE client and pushes notifications', function () {
    $port = random_int(40000, 49000);
    $server = new DevServer('127.0.0.1', $port);
    $server->start();

    $client = stream_socket_client("tcp://127.0.0.1:{$port}", $errno, $errstr, 2);
    expect($client)->not->toBeFalse();

    fwrite($client, "GET /__cooker-hmr HTTP/1.1\r\nHost: 127.0.0.1\r\n\r\n");
    fflush($client);

    // Wait for server to handshake.
    $deadline = microtime(true) + 1.5;
    while ($server->clientCount() === 0 && microtime(true) < $deadline) {
        $server->tick();
        usleep(20_000);
    }
    expect($server->clientCount())->toBe(1);

    // Read the headers + initial hello event.
    stream_set_blocking($client, false);
    $deadline = microtime(true) + 1.5;
    $buffer = '';
    while (!str_contains($buffer, "event: hello") && microtime(true) < $deadline) {
        $chunk = fread($client, 8192);
        if ($chunk !== false && $chunk !== '') {
            $buffer .= $chunk;
        }
        usleep(20_000);
    }
    expect($buffer)->toContain('text/event-stream');
    expect($buffer)->toContain('event: hello');

    // Push a reload event and confirm the client receives it.
    $server->notify('reload', ['why' => 'test']);
    $deadline = microtime(true) + 1.5;
    while (!str_contains($buffer, 'event: reload') && microtime(true) < $deadline) {
        $chunk = fread($client, 8192);
        if ($chunk !== false && $chunk !== '') {
            $buffer .= $chunk;
        }
        usleep(20_000);
    }
    expect($buffer)->toContain('event: reload');
    expect($buffer)->toContain('"why":"test"');

    fclose($client);
    $server->stop();
});
