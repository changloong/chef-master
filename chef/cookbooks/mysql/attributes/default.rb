# service

if node['mysql']['service'].has_key?('name')
    default['mysql']['service']['name'] = node['mysql']['service']['name']
else
    default['mysql']['service']['name'] = 'mysqld'
end

if node['mysql']['service'].has_key?('service_name')
    default['mysql']['service']['service_name'] = node['mysql']['service']['service_name']
else
    default['mysql']['service']['service_name'] = "mysql_#{default['mysql']['service']['name']}"
end

if node['mysql']['service'].has_key?('dir')
    default['mysql']['service']['dir'] = node['mysql']['service']['dir']
else
    default['mysql']['service']['dir'] = "/opt/local/mysql56"
end

if node['mysql']['mysqld'].has_key?('basedir')
    default['mysql']['mysqld']['basedir'] = "#{node['mysql']['mysqld']['basedir']}"
else
    default['mysql']['mysqld']['basedir'] = default['mysql']['service']['dir']
end

if node['mysql']['service'].has_key?('bin_dir')
    default['mysql']['service']['bin_dir'] = "#{node['mysql']['service']['bin_dir']}"
else
    default['mysql']['service']['bin_dir'] = "#{default['mysql']['mysqld']['basedir']}/bin"
end

if node['mysql']['service'].has_key?('wrap_bin_dir')
    default['mysql']['service']['wrap_bin_dir'] = "#{node['mysql']['service']['wrap_bin_dir']}"
else
    default['mysql']['service']['wrap_bin_dir'] = "#{default['mysql']['service']['dir']}/wbin"
end

if node['mysql']['service'].has_key?('etc_dir')
    default['mysql']['service']['etc_dir'] = "#{node['mysql']['service']['etc_dir']}"
else
    default['mysql']['service']['etc_dir'] = "#{default['mysql']['service']['dir']}/etc"
end

default['mysql']['service']['mysqld_bin'] = "#{default['mysql']['service']['bin_dir']}/mysqld"
default['mysql']['service']['mysql_install_db'] = "#{default['mysql']['mysqld']['basedir']}/scripts/mysql_install_db"

# password for root
default['mysql']['service']['password'] = 'ilikerandompasswords'

# mysqld
if node['mysql']['service'].has_key?('datadir')
    default['mysql']['mysqld']['datadir'] = "#{node['mysql']['mysqld']['datadir']}"
else
    default['mysql']['mysqld']['datadir'] = "#{default['mysql']['service']['dir']}/data"
end

if node['mysql']['mysqld'].has_key?('logdir')
    default['mysql']['mysqld']['logdir']  = "#{node['mysql']['mysqld']['logdir']}"
else
    default['mysql']['mysqld']['logdir']  = "#{default['mysql']['service']['dir']}/log"
end

if node['mysql']['service'].has_key?('tmpdir')
   default['mysql']['mysqld']['tmpdir'] = "#{node['mysql']['mysqld']['tmpdir']}"
else
   default['mysql']['mysqld']['tmpdir'] = "#{default['mysql']['service']['dir']}/tmp"
end

default['mysql']['mysqld']['port'] = '3306'
default['mysql']['mysqld']['socket'] = "#{default['mysql']['mysqld']['tmpdir']}/mysql.sock"
default['mysql']['mysqld']['pid_file'] = "#{default['mysql']['mysqld']['tmpdir']}/mysql.pid"
default['mysql']['mysqld']['character_set_server'] = 'utf8'
default['mysql']['mysqld']['collation_server'] = 'utf8_unicode_ci'

lib_dir = 'lib'

case node['platform_family']
when 'rhel', 'fedora'
  lib_dir = node['kernel']['machine'] =~ /x86_64/ ? 'lib64' : 'lib'
when 'suse'
  lib_dir = node['kernel']['machine'] =~ /x86_64/ ? 'lib64' : 'lib'
end

