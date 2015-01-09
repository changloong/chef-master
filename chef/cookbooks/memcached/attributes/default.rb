# service

if node['memcached']['service'].has_key?('name')
    default['memcached']['service']['name'] = node['memcached']['service']['name']
else
    default['memcached']['service']['name'] = 'memcached'
end

if node['memcached']['service'].has_key?('dir')
    default['memcached']['service']['dir'] = node['memcached']['service']['dir']
else
    default['memcached']['service']['dir'] = "/opt/local/memcached"
end

if node['memcached']['service'].has_key?('bin_dir')
    default['memcached']['service']['bin_dir'] = "#{node['memcached']['service']['bin_dir']}"
else
    default['memcached']['service']['bin_dir'] = "#{default['memcached']['service']['dir']}/bin"
end

if node['memcached']['service'].has_key?('etc_dir')
    default['memcached']['service']['etc_dir'] = "#{node['memcached']['service']['etc_dir']}"
else
    default['memcached']['service']['etc_dir'] = "#{default['memcached']['service']['dir']}/etc"
end

if node['memcached']['service'].has_key?('datadir')
    default['memcached']['service']['datadir'] = "#{node['memcached']['service']['datadir']}"
else
    default['memcached']['service']['datadir'] = "#{default['memcached']['service']['dir']}/data"
end

if node['memcached']['service'].has_key?('logdir')
    default['memcached']['service']['logdir']  = "#{node['memcached']['service']['logdir']}"
else
    default['memcached']['service']['logdir']  = "#{default['memcached']['service']['dir']}/log"
end

if node['memcached']['service'].has_key?('tmpdir')
   default['memcached']['service']['tmpdir'] = "#{node['memcached']['service']['tmpdir']}"
else
   default['memcached']['service']['tmpdir'] = "#{default['memcached']['service']['dir']}/tmp"
end

default['memcached']['service']['bin'] = "#{default['memcached']['service']['bin_dir']}/memcached"
default['memcached']['service']['port'] = '11211'
default['memcached']['service']['pid_file'] = "#{default['memcached']['service']['tmpdir']}/memcached.pid"

default['memcached']['service']['user'] = 'nobody'

lib_dir = 'lib'
case node['platform_family']
when 'rhel', 'fedora'
  lib_dir = node['kernel']['machine'] =~ /x86_64/ ? 'lib64' : 'lib'
when 'suse'
  lib_dir = node['kernel']['machine'] =~ /x86_64/ ? 'lib64' : 'lib'
end

