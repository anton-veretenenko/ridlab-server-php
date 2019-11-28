<?php

set_time_limit(0);
ini_set("memory_limit", "128M");
//error_reporting(0);

require_once("handler.php");

if (!extension_loaded('libevent')) {
    die("libevent extension required.\n");
}

class SServer
{
    private $socket, $base, $event;
    private $handlers = array();
    private $buf_handlers = array();
    private $clients = array();
    private $id = 0;

    function __construct($ip, $port)
    {
        $errstr = '';
        $errno = 0;
        $port = intval($port);
        $this->socket = stream_socket_server("tcp://{$ip}:{$port}", $errno, $errstr);
        if (!$this->socket) {
            die("{$errstr} ({$errno})");
        }

        stream_set_blocking($this->socket, 0);
        $this->base = event_base_new();
        $this->event = event_new();
        event_set($this->event, $this->socket, EV_READ | EV_PERSIST, array($this, 'event_accept'), $this->base);
        event_base_set($this->event, $this->base);
        event_add($this->event);
        event_base_loop($this->base);
    }

    function event_accept($sock, $flag, $base)
    {
        $client_socket = stream_socket_accept($sock);
        stream_set_blocking($client_socket, 0);

        $buf_handler = event_buffer_new(
                        $client_socket,
                        array($this, 'event_read'),
                        array($this, 'event_write'),
                        array($this, 'event_error'),
                        $this->id
                    );
        event_buffer_base_set($buf_handler, $base);
        event_buffer_timeout_set($buf_handler, 30, 30);
        event_buffer_watermark_set($buf_handler, EV_READ, 0, 0xffffff);
        event_buffer_priority_set($buf_handler, 10);
        event_buffer_enable($buf_handler, EV_READ | EV_PERSIST);
        $this->handlers[$this->id] = new SEHandler($buf_handler);
        $this->buf_handlers[$this->id] = $buf_handler;
        $this->clients[$this->id] = $client_socket;

        $this->id++;
    }

    function event_error($buf_handler, $error, $id)
    {
        event_buffer_disable($this->buf_handlers[$id], EV_READ | EV_WRITE);
        event_buffer_free($this->buf_handlers[$id]);
        $this->handlers[$id]->close();
        fclose($this->clients[$id]);
        unset($this->buf_handlers[$id]);
        unset($this->handlers[$id]);
        unset($this->clients[$id]);
    }

    function event_read($buf_handler, $id)
    {
        $this->handlers[$id]->read();
    }

    function event_write($buf_handler, $id)
    {
        if (!$this->handlers[$id]->write()) {
            $this->event_error($buf_handler, '', $id);
        }
    }
}

$s = new SServer('127.0.0.1', 8181);

