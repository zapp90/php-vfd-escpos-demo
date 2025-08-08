<?php
namespace Vfd;

class VfdDisplay {
    private $stream;
    private $type; // 'serial' | 'com' | 'tcp'
    private $target;

    private function __construct($stream, $type, $target) {
        $this->stream = $stream;
        $this->type = $type;
        $this->target = $target;
        stream_set_blocking($this->stream, true);
        if ($this->type !== 'tcp') {
            stream_set_timeout($this->stream, 1);
        }
    }

    public static function serial(string $path) : self {
        $fp = @fopen($path, 'w+');
        if (!$fp) {
            throw new \RuntimeException("Cannot open serial port: $path");
        }
        return new self($fp, 'serial', $path);
    }

    public static function com(string $port) : self {
        if (stripos($port, 'COM') !== 0) {
            $port = 'COM' . $port;
        }
        // Windows needs trailing colon
        if (substr($port, -1) !== ':') $port .= ':';

        $fp = @fopen($port, 'w+');
        if (!$fp) {
            throw new \RuntimeException("Cannot open COM port: $port");
        }
        return new self($fp, 'com', $port);
    }

    public static function tcp(string $host, int $port = 9100) : self {
        $sock = @fsockopen($host, $port, $errno, $errstr, 2);
        if (!$sock) {
            throw new \RuntimeException("TCP connect failed: $errstr ($errno)");
        }
        return new self($sock, 'tcp', "$host:$port");
    }

    public function init(): self {
        $this->write("\x1B\x40"); // ESC @ initialize
        return $this;
    }

    public function clear(): self {
        $this->write("\x0C");     // Form feed / clear screen on VFDs
        return $this;
    }

    /**
     * Write up to 20 chars on the given line (1 or 2 for typical 2x20 displays).
     * Pads/truncates to 20.
     */
    public function writeLine(string $text, int $line) : self {
        $text = $this->fit20($text);
        // Move cursor: many VFDs use CR/LF to go to next line; simplest approach:
        // Clear, then write both lines when line=1/2 distinctions matter.
        // Here we do a simple approach: if writing line 2, prepend CRLF.
        if ($line === 1) {
            $this->home();
            $this->write($text);
        } else {
            $this->home();
            $this->write($this->fit20('')); // move to line 1 (write blanks)
            $this->write("\r\n");
            $this->write($text);
        }
        return $this;
    }

    public function showWelcome(string $storeName = 'WELCOME') : self {
        return $this->init()->clear()
            ->writeLine($this->fit20($storeName), 1)
            ->writeLine('READY', 2);
    }

    public function showItem(string $name, $price) : self {
        $line1 = $this->fit20($name);
        $line2 = $this->fit20(sprintf('RM %s', number_format((float)$price, 2)));
        return $this->init()->clear()
            ->writeLine($line1, 1)
            ->writeLine($line2, 2);
    }

    public function showTotal($amount) : self {
        $line1 = $this->fit20('TOTAL');
        $line2 = $this->fit20(sprintf('RM %s', number_format((float)$amount, 2)));
        return $this->init()->clear()
            ->writeLine($line1, 1)
            ->writeLine($line2, 2);
    }

    public function close(): void {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    private function home(): void {
        // Home cursor: CR+LF usually moves lines; some displays support explicit commands.
        // We'll reset + clear above to guarantee home position.
        // Here, send carriage return to ensure line start.
        $this->write("\r");
    }

    private function fit20(string $s) : string {
        // ensure ASCII-safe for VFD; truncate/pad to 20
        $s = preg_replace('/\s+/', ' ', $s ?? '');
        $s = mb_substr($s, 0, 20, 'UTF-8');
        // pad with spaces to 20 columns
        return str_pad($s, 20, ' ', STR_PAD_RIGHT);
    }

    private function write(string $bytes): void {
        $len = strlen($bytes);
        $written = 0;
        while ($written < $len) {
            $n = fwrite($this->stream, substr($bytes, $written));
            if ($n === false) {
                throw new \RuntimeException("Write failed on {$this->type} {$this->target}");
            }
            $written += $n;
        }
        fflush($this->stream);
    }
}
