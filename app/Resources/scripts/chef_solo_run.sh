#!/bin/sh

if [ $# -eq 0 ]; then
        echo "usage: $0 <config file>"
        exit 1;
fi

chef_solo_config_file=$1

if [ ! -e $chef_solo_config_file ]; then
	echo "chef solo config file '$chef_solo_config_file' not exists"
	exit 2;
fi

export PATH=/opt/chef/bin:$PATH
export GIT_SSH=$HOME/.chefsolo/bin/git_ssh.sh
chef-solo -c ~/.chefsolo/src/chef_solo_setup.rb -j $chef_solo_config_file