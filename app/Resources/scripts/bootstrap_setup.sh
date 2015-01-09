
rm -f "$0"

{{ fs_write('/etc/resolv.conf', render('resolv.conf', {'client': client }, "0644" ) ) }}

if [ ! -d "$HOME/.chefsolo/cookbooks" ]; then
    mkdir -p "$HOME/.chefsolo/cookbooks"
fi

if [ ! -d "$HOME/.chefsolo/cache" ]; then
    mkdir "$HOME/.chefsolo/cache"
fi

if [ ! -d "$HOME/.chefsolo/config" ]; then
    mkdir "$HOME/.chefsolo/config"
fi

if [ ! -d "$HOME/.chefsolo/data_bags" ]; then
    mkdir "$HOME/.chefsolo/data_bags"
fi

if [ ! -d "$HOME/.chefsolo/roles" ]; then
    mkdir "$HOME/.chefsolo/roles"
fi

if [ ! -d "$HOME/.chefsolo/src" ]; then
    mkdir "$HOME/.chefsolo/src"
fi

if [ ! -d "$HOME/.chefsolo/bin" ]; then
    mkdir "$HOME/.chefsolo/bin"
fi

{{ fs_copy(git_deploy.local, git_deploy.remote, "0400") }}
{{ fs_write('~/.chefsolo/bin/git_ssh.sh', render('git_ssh.sh', { 'client': client } ), "0500" ) }}

installed="no"
if [ -e /opt/chef/bin/knife ]; then
    if [ -x /opt/chef/bin/knife ]; then
        installed="yes"
    fi
fi

if ! which rsync > /dev/null; then
   echo -e "rsync not found! Install..."
   yum -y install rsync
fi

if [ "no" == "$installed" ]; then
    if ! which wget > /dev/null; then
       echo -e "wget not found! Install..."
       yum -y install wget
    fi

    if [ -d /etc/yum.repos.d ]; then
        echo ">>>: yum"
        wget {{ chef_url.rpm }} -O chef_client.rpm
        rpm -ivh chef_client.rpm
    fi

    finished="no"
    if [ -e /opt/chef/bin/knife ]; then
        if [ -x /opt/chef/bin/knife ]; then
            finished="yes"
        fi
    fi

    if [ "yes" == "$installed" ]; then
        echo "chef install finished!"
    else
        echo "chef install not finished!"
    fi
fi

if [ "yes" == "$installed" ]; then
     echo "chef already installed!"
fi