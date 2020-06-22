chown -R www-data /var/www/storage /var/www/bootstrap/cache
cp /var/www/.env.dev /var/www/.env
chown www-data /var/www/.env
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf