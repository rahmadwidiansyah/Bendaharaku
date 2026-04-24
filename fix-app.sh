#!/bin/bash
echo "Memperbaiki Permission..."
sudo chown -R $USER:$USER .
sudo chmod -R 777 storage bootstrap/cache public/build

echo "Membersihkan Cache Laravel..."
docker compose exec app php artisan optimize:clear

echo "Menghubungkan Storage..."
rm -rf public/storage
docker compose exec app php artisan storage:link

echo "Selesai! Bendaharaku siap dipakai."
