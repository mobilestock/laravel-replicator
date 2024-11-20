#!/bin/bash
set -e

service mariadb start

until mysqladmin ping -h "localhost" --silent; do
    sleep 1
done

mysql -e "CREATE USER 'COLABORADOR_CENTRAL'@'%' IDENTIFIED BY 'COLABORADOR_CENTRAL';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'COLABORADOR_CENTRAL'@'%';"
mysql -e "GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO 'COLABORADOR_CENTRAL'@'%';"
mysql -e "FLUSH PRIVILEGES;"
mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root -D mysql

if [ "$#" -gt 0 ]; then
    exec "$@"
else
    tail -f /dev/null
fi
