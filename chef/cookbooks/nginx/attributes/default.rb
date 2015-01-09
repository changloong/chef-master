#
# Cookbook Name:: nginx

default['nginx']['service']['package_name'] = 'nginx'

if node['nginx']['service'].has_key?('prefix_dir')
    default['nginx']['service']['prefix_dir'] = node['nginx']['service']['prefix_dir']
else
    default['nginx']['service']['prefix_dir'] = default['nginx']['service']['prefix_dir']
end
if node['nginx']['service'].has_key?('etc_dir')
    default['nginx']['service']['etc_dir'] = node['nginx']['service']['etc_dir']
else
    default['nginx']['service']['etc_dir'] = "#{default['nginx']['service']['prefix_dir']}/etc"
end

default['nginx']['service']['log_dir']      = "#{default['nginx']['service']['prefix_dir']}/log"
default['nginx']['service']['tmp_dir']      = "#{default['nginx']['service']['prefix_dir']}/tmp"
default['nginx']['service']['bin']       = "#{default['nginx']['service']['prefix_dir']}/sbin/nginx"
default['nginx']['service']['conf_dir']     = "#{default[:nginx][:service][:etc_dir]}/conf.d"
default['nginx']['service']['pid'] = "#{default['nginx']['service']['tmp_dir']}/nginx.pid"

case node['platform_family']
when 'debian'
  default['nginx']['service']['user']       = 'www-data'
when 'rhel', 'fedora'
  default['nginx']['service']['user']       = 'nginx'
when 'gentoo'
  default['nginx']['service']['user']       = 'www'
else
  default['nginx']['service']['user']       = 'www-data'
end

default['nginx']['service']['group'] = node['nginx']['service']['user']

default['nginx']['site']['name']       = 'default' ;
default['nginx']['site']['root']       = '/opt/web/default' ;
default['nginx']['site']['autoindex']  = 'on' ;
default['nginx']['site']['listen']     = '0.0.0.0:80'
default['nginx']['site']['ssl']             = false
default['nginx']['site']['https_port']      = 443
default['nginx']['site']['server_name']     = node['hostname']
default['nginx']['site']['default_server']  = false


default['nginx']['service']['gzip']              = 'on'
default['nginx']['service']['gzip_static']       = 'off'
default['nginx']['service']['gzip_http_version'] = '1.0'
default['nginx']['service']['gzip_comp_level']   = '2'
default['nginx']['service']['gzip_proxied']      = 'any'
default['nginx']['service']['gzip_vary']         = 'off'
default['nginx']['service']['gzip_buffers']      = nil
default['nginx']['service']['gzip_types'] = %w(
  text/plain
  text/css
  application/x-javascript
  text/xml
  application/xml
  application/rss+xml
  application/atom+xml
  text/javascript
  application/javascript
  application/json
  text/mathml
)
default['nginx']['service']['gzip_min_length']   = 1_000
default['nginx']['service']['gzip_disable']      = 'MSIE [1-6]\.'

default['nginx']['service']['keepalive']            = 'on'
default['nginx']['service']['keepalive_timeout']    = 65
default['nginx']['service']['worker_processes']     = node['cpu'] && node['cpu']['total'] ? node['cpu']['total'] : 1
default['nginx']['service']['worker_connections']   = 1_024
default['nginx']['service']['worker_rlimit_nofile'] = nil
default['nginx']['service']['multi_accept']         = false
default['nginx']['service']['event']                = nil
default['nginx']['service']['server_tokens']        = nil
default['nginx']['service']['server_names_hash_bucket_size'] = 64
default['nginx']['service']['sendfile'] = 'on'

default['nginx']['service']['access_log_options']     = nil
default['nginx']['service']['error_log_options']      = nil
default['nginx']['service']['disable_access_log']     = false
default['nginx']['service']['install_method']         = 'package'
default['nginx']['service']['default_site_enabled']   = true
default['nginx']['service']['types_hash_max_size']    = 2_048
default['nginx']['service']['types_hash_bucket_size'] = 64

default['nginx']['service']['proxy_read_timeout']      = nil
default['nginx']['service']['client_body_buffer_size'] = nil
default['nginx']['service']['client_max_body_size']    = nil
default['nginx']['service']['default']['modules']      = []
