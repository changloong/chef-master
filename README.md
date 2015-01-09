Install on workstation
=========================


# clone code 
```sh
git clone gig@192.168.10.20:chef-master.git
cd chef-master
```

# install composer.phar
```sh
php -r "readfile('https://getcomposer.org/installer');" | php
```

# install vendor
```sh
php ./composer.phar install -vvv
```

# configure database

create file `app/config/local.php`
```php
<?php
$app['env.user'] = 'www' ;
$app['env.group'] = 'www' ;
$app['db.options'] = array(
    'driver'   => 'pdo_mysql',
    'host'     => 'localhost',
    'dbname'   => 'chef_master',
    'user'     => 'root',
    'password' => '' ,
);
```
create schema or update schema
```sh
./app/console  orm:schema-tool:update --force
```

# setup site-cookbooks

```sh
./app/console knife:setup 
```

# create clould env
```sh
./app/console knife:env:create
```

# create clould client
```sh
./app/console knife:client:create
```

# create clould recipe
```sh
./app/console knife:recipe:create
```

# runing clould recipe on clould client
```sh
./app/console knife:recipe:exec
```


