rm -f "$0"

{{ fs_write('~/.chefsolo/bin/chef_solo_run.sh', render('chef_solo_run.sh', { 'client': client } ), "0500" ) }}
{{ fs_write('~/.chefsolo/src/chef_solo_setup.rb', render('chef_solo_setup.rb', { 'client': client, 'debug': debug } ), "0400" ) }}

{{ fs_write(recipe_config, recipe_data ) }}

export PATH=/opt/chef/bin:$PATH
export GIT_SSH=$HOME/.chefsolo/bin/git_ssh.sh

{% for script, remote_path in knife_recipe_scripts %}
    {{ fs_write(remote_path, render(script, { 'client': client, 'recipe': recipe, 'debug': debug, 'data_bag': recipe_data_bag } ), "0400" ) }}
{% endfor %}

chef-solo -c ~/.chefsolo/src/chef_solo_setup.rb -j {{ recipe_config }}