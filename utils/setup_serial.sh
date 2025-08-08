#!/usr/bin/env bash
# Usage: sudo bash utils/setup_serial.sh /dev/ttyUSB0 [baud]
DEV=${1:-/dev/ttyUSB0}
BAUD=${2:-9600}
stty -F "$DEV" $BAUD cs8 -cstopb -parenb -ixon -ixoff -crtscts -echo
echo "Configured $DEV at $BAUD 8N1 (no flow, no echo)"
