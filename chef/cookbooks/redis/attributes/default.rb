# service

if node['redis']['service'].has_key?('name')
    default['redis']['service']['name'] = node['redis']['service']['name']
else
    default['redis']['service']['name'] = 'redis'
end

if node['redis']['service'].has_key?('dir')
    default['redis']['service']['dir'] = node['redis']['service']['dir']
else
    default['redis']['service']['dir'] = "/opt/local/redis"
end

if node['redis']['service'].has_key?('bin_dir')
    default['redis']['service']['bin_dir'] = "#{node['redis']['service']['bin_dir']}"
else
    default['redis']['service']['bin_dir'] = "#{default['redis']['service']['dir']}/bin"
end

if node['redis']['service'].has_key?('etc_dir')
    default['redis']['service']['etc_dir'] = "#{node['redis']['service']['etc_dir']}"
else
    default['redis']['service']['etc_dir'] = "#{default['redis']['service']['dir']}/etc"
end

if node['redis']['service'].has_key?('datadir')
    default['redis']['service']['datadir'] = "#{node['redis']['service']['datadir']}"
else
    default['redis']['service']['datadir'] = "#{default['redis']['service']['dir']}/data"
end

if node['redis']['service'].has_key?('logdir')
    default['redis']['service']['logdir']  = "#{node['redis']['service']['logdir']}"
else
    default['redis']['service']['logdir']  = "#{default['redis']['service']['dir']}/log"
end

if node['redis']['service'].has_key?('tmpdir')
   default['redis']['service']['tmpdir'] = "#{node['redis']['service']['tmpdir']}"
else
   default['redis']['service']['tmpdir'] = "#{default['redis']['service']['dir']}/tmp"
end

default['redis']['service']['bin'] = "#{default['redis']['service']['bin_dir']}/redis-server"
default['redis']['service']['port'] = 6379
default['redis']['service']['pid_file'] = "#{default['redis']['service']['tmpdir']}/redis.pid"

default['redis']['service']['user'] = 'nobody'

lib_dir = 'lib'
case node['platform_family']
when 'rhel', 'fedora'
  lib_dir = node['kernel']['machine'] =~ /x86_64/ ? 'lib64' : 'lib'
when 'suse'
  lib_dir = node['kernel']['machine'] =~ /x86_64/ ? 'lib64' : 'lib'
end

