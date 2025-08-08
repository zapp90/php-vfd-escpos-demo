# php-vfd-escpos-demo
Send text from **PHP** to an **ESC/POS-compatible VFD customer display** (Epson DM‑D, generic 2x20, etc.) via **serial/USB**, **Windows COM**, or **TCP (9100)** pass-through.

## What this repo contains
- `src/VfdDisplay.php` – tiny helper to talk to a VFD
- `examples/linux_serial.php` – write to `/dev/ttyUSB0` (Linux/Ubuntu)
- `examples/windows_com.php` – write to `COM3` (Windows)
- `examples/tcp_passthrough.php` – send via printer’s TCP `9100` (pass-through)
- `utils/setup_serial.sh` – one-time serial port setup (Linux)
- `utils/setup_com.bat` – one-time COM config (Windows)
- `LICENSE` – MIT

> Default settings are **9600 8N1**, no flow control. Check your device’s DIP switch/manual.

## Quick Start

### Linux (USB-Serial)
1. Connect your VFD (e.g., Epson DM‑D) via USB‑Serial (shows up as `/dev/ttyUSB0`).
2. Configure the port once:
   ```bash
   sudo bash utils/setup_serial.sh /dev/ttyUSB0
   ```
3. Run the example:
   ```bash
   php examples/linux_serial.php
   ```

### Windows (COM port)
1. Plug the display (or via printer’s display port) and find the COM number (e.g., COM3).
2. Configure once:
   ```bat
   utils\setup_com.bat COM3
   ```
3. Run:
   ```bat
   php examples\windows_com.php
   ```

### Network (TCP 9100 pass-through)
Some Epson TM printers forward display commands from TCP 9100 to the customer display port.
```bash
php examples/tcp_passthrough.php
```

## API (VfdDisplay.php)
```php
use Vfd\VfdDisplay;

$vd = VfdDisplay::serial('/dev/ttyUSB0');   // or ::com('COM3') or ::tcp('192.168.1.50', 9100)
$vd->init()->clear();
$vd->writeLine('WELCOME TO ZAPP POS', 1);
$vd->writeLine('TOTAL: RM 15.00', 2);
$vd->close();
```

## Notes
- Most VFDs are **2×20 chars**. Pad/truncate to 20 per line.
- If you see gibberish: baud/bit/parity mismatch.
- If the **printer** prints instead of the display: you’re sending printer commands or not routing to the display port.
- Codepage issues: these examples send ASCII. For full UTF‑8, consult your device’s codepage commands.

## License
MIT
