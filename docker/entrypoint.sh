#!/bin/sh
set -e

MODE="${1:-${RUN_MODE:-apache-only}}"

case "$MODE" in
    apache-only|apache)
        echo "Entrypoint: running Apache only"
        exec apache2-foreground
        ;;
    ai-parser-only|ai-parser)
        echo "Entrypoint: running AI Parser only"
        cd /var/www/script_pencatat_keuangan
        exec /opt/venv/bin/uvicorn app.main:app --host 0.0.0.0 --port 3987 --workers 2
        ;;
    *)
        echo "Entrypoint: unknown mode '$MODE', falling back to Apache only"
        exec apache2-foreground
        ;;
esac
