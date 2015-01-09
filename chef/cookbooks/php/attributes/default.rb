#
# Cookbook Name:: php
# Attribute:: default

default['php']['install_method'] = 'source'
default['php']['directives'] = {}
default['php']['bin'] = 'php'

default['php']['ini']['upload_tmp_dir'] = '/tmp'
default['php']['ini']['upload_max_filesize'] = '8M'
default['php']['ini']['post_max_size'] = '8M'

default['php']['fpm_bin'] = '/usb/sbin/php-fpm'
default['php']['fpm_pid'] = '/var/run/php-fpm.pid'
default['php']['fpm_log_dir']   = '/var/log/php'
default['php']['fpm_pool']['name'] = 'www'
default['php']['fpm_pool']['listen']   = '127.0.0.1:9000'

default['php']['fpm_pool']['pm'] = 'dynamic'
default['php']['fpm_pool']['max_children'] = 5
default['php']['fpm_pool']['start_servers'] = 2
default['php']['fpm_pool']['min_spare_servers'] = 1
default['php']['fpm_pool']['max_spare_servers'] = 3
default['php']['fpm_pool']['process_idle_timeout'] = '10s'
default['php']['fpm_pool']['max_requests'] = 500
default['php']['fpm_pool']['request_slowlog_timeout'] = 0
default['php']['fpm_pool']['request_terminate_timeout'] = 0
default['php']['fpm_pool']['rlimit_files'] = 1024
default['php']['fpm_pool']['rlimit_core'] = 0
default['php']['fpm_pool']['limit_extensions'] = '.php .php5'

default['php']['fpm_pool']['sendmail_path'] = '/usr/sbin/sendmail -t -i -f www@my.domain.com'
default['php']['fpm_pool']['display_errors'] = 'on'
default['php']['fpm_pool']['log_errors'] = 'on'
default['php']['fpm_pool']['memory_limit'] = '32M'

default['php']['fpm_pool']['clear_env'] = 'no'

default['php']['fpm_pool']['env']['PATH'] = '/usr/local/bin:/usr/bin:/bin'
default['php']['fpm_pool']['env']['TMP']  = '/tmp'

case node['platform_family']
when 'rhel', 'fedora'
  default['php']['conf_dir']      = '/etc'
  default['php']['ext_conf_dir']  = '/etc/php.d'
  if node['platform_version'].to_f < 6
    default['php']['packages'] = %w{ php53 php53-devel php53-cli php-pear }
  else
    default['php']['packages'] = %w{ php php-devel php-cli php-pear }
  end

  default['php']['fpm_pool']['user']      = 'nobody'
  default['php']['fpm_pool']['group']     = 'nobody'

when 'debian'
  default['php']['conf_dir']      = '/etc/php5/cli'
  default['php']['ext_conf_dir']  = '/etc/php5/conf.d'
  default['php']['packages']      = %w{ php5-cgi php5 php5-dev php5-cli php-pear }

  default['php']['fpm_conf_dir']     = '/etc/php5/fpm'
  default['php']['fpm_pool']['user']      = 'www-data'
  default['php']['fpm_pool']['group']     = 'www-data'

when 'suse'
  default['php']['conf_dir']      = '/etc/php5/cli'
  default['php']['ext_conf_dir']  = '/etc/php5/conf.d'
  default['php']['packages']      = %w{ apache2-mod_php5 php5-pear }
  lib_dir = node['kernel']['machine'] =~ /x86_64/ ? 'lib64' : 'lib'

  default['php']['fpm_conf_dir']  = '/etc/php5/fpm'
  default['php']['fpm_pool']['user']      = 'wwwrun'
  default['php']['fpm_pool']['group']     = 'www'

else
  default['php']['conf_dir']      = '/etc/php5/cli'
  default['php']['ext_conf_dir']  = '/etc/php5/conf.d'
  default['php']['packages']      = %w{ php5-cgi php5 php5-dev php5-cli php-pear }

  default['php']['fpm_conf_dir']  = '/etc/php5/fpm'
  default['php']['fpm_pool']['user']      = 'www-data'
  default['php']['fpm_pool']['group']     = 'www-data'

end

default['php']['service']['url'] = 'http://us1.php.net/get'
default['php']['service']['version'] = '5.5.16'
default['php']['service']['checksum'] = '5def6d89792caa70448c67cd510e0f3e'
default['php']['service']['prefix_dir'] = '/usr/local/webserver/php55'

case node['php']['install_method']
when 'source'
  if  node['php']['service'].has_key?("prefix_dir")
      default['php']['service']['prefix_dir'] = node['php']['service']['prefix_dir']
  end
  default['php']['conf_dir']     = "#{default['php']['service']['prefix_dir']}/etc"
  default['php']['ext_conf_dir'] = "#{default['php']['service']['prefix_dir']}/etc/php.d"
  default['php']['bin']  = "#{default['php']['service']['prefix_dir']}/bin/php"
  default['php']['service']['php_src_url']   = "#{default['php']['service']['url']}/php-#{default['php']['version']}.tar.gz/from/this/mirror"

  default['php']['fpm_bin']  = "#{default['php']['service']['prefix_dir']}/sbin/php-fpm"
  if not node['php'].has_key?("fpm_conf_dir")
        default['php']['fpm_conf_dir'] = "#{default['php']['service']['prefix_dir']}/etc"
  end

end
