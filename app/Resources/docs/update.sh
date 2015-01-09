#!/usr/bin/env bash

export PATH=/usr/local/webserver/php5_5_15/bin:$PATH

pushd $(dirname "${0}") > /dev/null
basedir=$(pwd -L)
popd > /dev/null
cd $basedir
cd ..
php ./console app:setup
