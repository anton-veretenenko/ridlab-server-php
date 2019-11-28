<?php

class FHandler
{
    private $file = NULL;
    private $size = 0;

    function __construct($request)
    {
        $request = trim($request);
        $data = explode(' ', $request);
        if (strtolower($data[0]) == 'get') {
            $path = str_replace(array("\\", "..", "./"), '', $data[1]);
            $path = dirname(__FILE__) . "/files" . $path;
            if (file_exists($path)) {
                $this->size = filesize($path);
                $this->file = fopen($path, "rb");
            }
        }
    }

    function file()
    {
        return $this->file;
    }

    function size()
    {
        return $this->size;
    }

    function close()
    {
        if ($this->file != NULL) {
            fclose($this->file);
            $this->file = NULL;
            $this->size = 0;
        }
    }
}

class SEHandler
{
    private $buf_handler;
    private $datain = '';
    private $dataout = '';
    private $fhandler = NULL;

    const resp_200 = "HTTP/1.0 200 OK\r\n";
    const resp_404 = "HTTP/1.0 404 Not Found\r\n\r\n";
    const resp_cont = "Content-Type: application/octet-stream\r\nContent-Length: %d\r\n\r\n";

    function __construct($buf_handler)
    {
        $this->buf_handler = $buf_handler;
    }

    function read()
    {
        $this->datain .= event_buffer_read($this->buf_handler, 1024);
        if (strpos($this->datain, "\n\n") !== false ||
            strpos($this->datain, "\r\n\r\n") !== false) {
            // request ended, need processing
            event_buffer_disable($this->buf_handler, EV_READ);
            //event_buffer_enable($this->buf_handler, EV_WRITE);
            $this->fhandler = new FHandler($this->datain);
            if ($this->fhandler->file() != NULL) {
                $this->dataout = sprintf(self::resp_200 . self::resp_cont, $this->fhandler->size());
                $this->write();
            } else {
                $this->dataout = self::resp_404;
                $this->write();
            }

        }
    }

    function write()
    {
        if (strlen($this->dataout) > 0) {
            if (event_buffer_write($this->buf_handler, $this->dataout)) {
                $this->dataout = '';
            }
        } else
        if ($this->fhandler != NULL && $this->fhandler->file() != NULL) {
            // transfer file
            if (ftell($this->fhandler->file()) == $this->fhandler->size()) {
                $this->fhandler->close();
                unset($this->fhandler);
                $this->fhandler = NULL;
                return false;
            } else {
                $data = fread($this->fhandler->file(), 32);
                if ($data !== false) {
                    event_buffer_write($this->buf_handler, $data);
                }
            }
        } else {
            return false;
        }

        return true;
    }

    function close()
    {
        if ($this->fhandler != NULL) {
            $this->fhandler->close();
        }
    }
}
