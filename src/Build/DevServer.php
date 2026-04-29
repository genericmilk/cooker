<?php

namespace Genericmilk\Cooker\Build;

use RuntimeException;

/**
 * Tiny embedded SSE server for live-reload during `cooker:watch`.
 *
 * Holds long-lived HTTP connections at /__cooker-hmr and pushes JSON-encoded
 * events to all connected browsers when the watcher rebuilds.
 */
class DevServer
{
    /** @var resource|null */
    protected $listener = null;

    /** @var array<int, resource> */
    protected array $clients = [];

    public function __construct(
        protected string $host = '127.0.0.1',
        protected int $port = 5173,
    ) {
    }

    public function start(): void
    {
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_server(
            "tcp://{$this->host}:{$this->port}",
            $errno,
            $errstr,
        );

        if ($socket === false) {
            throw new RuntimeException("Cooker dev server: could not bind {$this->host}:{$this->port} — {$errstr}");
        }

        stream_set_blocking($socket, false);
        $this->listener = $socket;
    }

    public function stop(): void
    {
        foreach ($this->clients as $client) {
            @fclose($client);
        }
        $this->clients = [];

        if (is_resource($this->listener)) {
            @fclose($this->listener);
        }
        $this->listener = null;
    }

    public function url(): string
    {
        return "http://{$this->host}:{$this->port}/__cooker-hmr";
    }

    public function clientCount(): int
    {
        return count($this->clients);
    }

    /**
     * Non-blocking: accept new connections, prune dead ones. Call every loop tick.
     */
    public function tick(): void
    {
        if (!is_resource($this->listener)) {
            return;
        }

        $read = [$this->listener];
        $write = $except = null;
        $count = @stream_select($read, $write, $except, 0, 0);
        if ($count > 0) {
            $client = @stream_socket_accept($this->listener, 0);
            if (is_resource($client)) {
                $this->handshake($client);
            }
        }

        foreach ($this->clients as $i => $client) {
            if (!is_resource($client) || feof($client)) {
                @fclose($client);
                unset($this->clients[$i]);
            }
        }
        $this->clients = array_values($this->clients);
    }

    public function notify(string $event, array $data = []): void
    {
        $payload = "event: {$event}\n";
        $payload .= 'data: '.json_encode($data, JSON_UNESCAPED_SLASHES)."\n\n";

        foreach ($this->clients as $i => $client) {
            $written = @fwrite($client, $payload);
            if ($written === false || $written === 0) {
                @fclose($client);
                unset($this->clients[$i]);
            }
        }
        $this->clients = array_values($this->clients);
    }

    protected function handshake($client): void
    {
        stream_set_blocking($client, true);
        stream_set_timeout($client, 1);

        $request = '';
        while (!feof($client)) {
            $line = fgets($client, 4096);
            if ($line === false || $line === "\r\n" || $line === "\n") {
                break;
            }
            $request .= $line;
        }

        if (!preg_match('#^GET\s+/__cooker-hmr#i', $request)) {
            $body = "Cooker dev server. Connect EventSource to /__cooker-hmr.";
            fwrite($client, "HTTP/1.1 404 Not Found\r\nContent-Type: text/plain\r\nAccess-Control-Allow-Origin: *\r\nConnection: close\r\nContent-Length: ".strlen($body)."\r\n\r\n".$body);
            fclose($client);
            return;
        }

        $headers = "HTTP/1.1 200 OK\r\n"
            ."Content-Type: text/event-stream\r\n"
            ."Cache-Control: no-cache, no-transform\r\n"
            ."Connection: keep-alive\r\n"
            ."Access-Control-Allow-Origin: *\r\n"
            ."X-Accel-Buffering: no\r\n"
            ."\r\n";

        fwrite($client, $headers);
        fwrite($client, "retry: 2000\n\nevent: hello\ndata: {}\n\n");

        stream_set_blocking($client, false);
        $this->clients[] = $client;
    }
}
