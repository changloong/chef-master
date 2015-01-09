
rm -f "$0"

if [ ! -d "$HOME/.ssh" ]; then
    mkdir "$HOME/.ssh"
fi

{{ fs_copy(authorized_file, '~/.ssh/authorized_keys' ) }}

echo env_client_home=$HOME
echo env_client_hostname=`hostname`
echo env_client_bash=`which bash`

{{ fs_write(bootstrap_setup_file, render('bootstrap_setup.sh', {'client': client }, "0400" ) ) }}