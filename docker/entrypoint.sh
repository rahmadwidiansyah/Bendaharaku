#!/bin/sh
set -e

MODE="${1:-${RUN_MODE:-apache-only}}"

case "$MODE" in
    apache-only|apache)
        echo "Entrypoint: running Apache only"
        exec apache2-foreground
        ;;

    queue)
        echo "Entrypoint: running Queue Worker"
        exec php artisan queue:work --sleep=2 --tries=3 --timeout=300
        ;;

    ai-parser-only|ai-parser)
        echo "Entrypoint: running AI Parser only"
        cd /var/www/script_pencatat_keuangan
        exec /opt/venv/bin/uvicorn app.main:app \
            --host 0.0.0.0 \
            --port 3987 \
            --workers 2
        ;;

    ocr-service|ocr)
        echo "Entrypoint: running OCR Service only"
        cd /var/www/script_pencatat_keuangan
        exec /opt/venv/bin/uvicorn ocr.main:app \
            --host 0.0.0.0 \
            --port 8000 \
            --workers 2
        ;;

    scheduler)
        echo "Entrypoint: running Scheduler loop"
        exec sh -c "while true; do php artisan schedule:run --verbose --no-interaction; sleep 60; done"
        ;;

    sh|bash)
        echo "Entrypoint: running custom shell command: $@"
        exec "$@"
        ;;

    *)
        echo "Entrypoint: unknown mode '$MODE', falling back to Apache only"
        exec apache2-foreground
        ;;
esac