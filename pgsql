Ubuntu

[33mubu     |[0m pdo_pgsql
[33mubu     |[0m
[33mubu     |[0m PDO Driver for PostgreSQL => enabled
[33mubu     |[0m PostgreSQL(libpq) Version => 11.7 (Ubuntu 11.7-0ubuntu0.19.10.1)

[33mubu     |[0m pgsql
[33mubu     |[0m
[33mubu     |[0m PostgreSQL Support => enabled
[33mubu     |[0m PostgreSQL(libpq) Version => 11.7 (Ubuntu 11.7-0ubuntu0.19.10.1)
[33mubu     |[0m PostgreSQL(libpq)  => PostgreSQL 11.7 (Ubuntu 11.7-0ubuntu0.19.10.1) on x86_64-pc-linux-gnu, compiled by gcc (Ubuntu 9.2.1-9ubuntu2) 9.2.1 20191008, 64-bit
[33mubu     |[0m Multibyte character support => enabled
[33mubu     |[0m SSL support => enabled
[33mubu     |[0m Active Persistent Links => 0
[33mubu     |[0m Active Links => 0
[33mubu     |[0m
[33mubu     |[0m Directive => Local Value => Master Value
[33mubu     |[0m pgsql.allow_persistent => On => On
[33mubu     |[0m pgsql.auto_reset_persistent => Off => Off
[33mubu     |[0m pgsql.ignore_notice => Off => Off
[33mubu     |[0m pgsql.log_notice => Off => Off
[33mubu     |[0m pgsql.max_links => Unlimited => Unlimited
[33mubu     |[0m pgsql.max_persistent => Unlimited => Unlimited

RedHat

[32mcent    |[0m pdo_pgsql
[32mcent    |[0m
[32mcent    |[0m PDO Driver for PostgreSQL => enabled
[32mcent    |[0m PostgreSQL(libpq) Version => 10.5

[32mcent    |[0m pgsql
[32mcent    |[0m
[32mcent    |[0m PostgreSQL Support => enabled
[32mcent    |[0m PostgreSQL(libpq) Version => 10.5
[32mcent    |[0m PostgreSQL(libpq)  => PostgreSQL 10.5 on x86_64-redhat-linux-gnu, compiled by gcc (GCC) 8.2.1 20180726 (Red Hat 8.2.1-1), 64-bit
[32mcent    |[0m Multibyte character support => enabled
[32mcent    |[0m SSL support => enabled
[32mcent    |[0m Active Persistent Links => 0
[32mcent    |[0m Active Links => 0
[32mcent    |[0m
[32mcent    |[0m Directive => Local Value => Master Value
[32mcent    |[0m pgsql.allow_persistent => On => On
[32mcent    |[0m pgsql.auto_reset_persistent => Off => Off
[32mcent    |[0m pgsql.ignore_notice => Off => Off
[32mcent    |[0m pgsql.log_notice => Off => Off
[32mcent    |[0m pgsql.max_links => Unlimited => Unlimited
[32mcent    |[0m pgsql.max_persistent => Unlimited => Unlimited

C:\Users\Master>docker exec -it cent bash
[root@4ddbfce3bdfe www]# php -v
PHP 7.4.5 (cli) (built: Apr 14 2020 12:54:33) ( NTS )
Copyright (c) The PHP Group
Zend Engine v3.4.0, Copyright (c) Zend Technologies
[root@4ddbfce3bdfe www]# exit
exit

C:\Users\Master>docker exec -it ubu bash
root@6c311afffa2f:/var/www# php -v
PHP 7.4.5 (cli) (built: Apr 19 2020 07:36:46) ( NTS )
Copyright (c) The PHP Group
Zend Engine v3.4.0, Copyright (c) Zend Technologies
    with Zend OPcache v7.4.5, Copyright (c), by Zend Technologies
