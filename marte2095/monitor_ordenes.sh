#!/bin/bash

DB_FILE="/var/www/marte2095/datos/ordenes.sqlite"
SCRIPT_ENVIO="$HOME/enviar_ordenes_telegram.sh"

echo "Monitoreando cambios en $DB_FILE..."
echo "Presiona Ctrl+C para detener."

# Inicia monitoreo en bucle infinito
inotifywait -m -e modify "$DB_FILE" | while read -r path event file; do
    echo "Cambio detectado en $DB_FILE, enviando a Telegram..."
    "$SCRIPT_ENVIO"
done
