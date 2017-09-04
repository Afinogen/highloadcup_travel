#!/bin/bash
#mysqld_safe &
sleep 6
echo "DROP DATABASE travel;" | mysql
echo "CREATE DATABASE travel;" | mysql
mysql travel < /var/www/html/db.sql
php  /var/www/html/prepare.php