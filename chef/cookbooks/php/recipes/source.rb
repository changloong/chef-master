#
# Cookbook Name:: php
# Recipe:: source

include_recipe "php::default"

include_recipe 'build-essential'
#include_recipe 'xml'
include_recipe 'yum-epel' if node['platform_family'] == 'rhel'


pkgs = value_for_platform_family(
  %w{ rhel fedora } => %w{ bzip2-devel libc-client-devel curl-devel freetype-devel gmp-devel libjpeg-devel libmcrypt-devel libpng-devel openssl-devel krb5-devel libcom_err-devel t1lib-devel mhash-devel libxml2-devel zlib-devel libicu-devel libssh2-devel },
  %w{ debian ubuntu } => %w{ libbz2-dev libc-client2007e-dev libcurl4-gnutls-dev libfreetype6-dev libgmp3-dev libjpeg62-dev libmcrypt-dev libpng12-dev libssl-dev libt1-dev  libxml2-dev },
  'default' => %w{ libbz2-dev libc-client2007e-dev libcurl4-gnutls-dev libfreetype6-dev libgmp3-dev libjpeg62-dev libmcrypt-dev libpng12-dev libssl-dev libt1-dev }
  )

pkgs.each do |pkg|
  package pkg do
    action :install
  end
end

puts "=============== install php ==================="
puts node['php']

lib_dir = 'lib'
case node['platform_family']
when 'rhel', 'fedora'
  lib_dir = node['kernel']['machine'] =~ /x86_64/ ? 'lib64' : 'lib'
end


# --disable-libxml --disable-dom
# --enable-libxml --with-libxml-dir
# --with-kerberos
configure_options = %W{--prefix=#{node['php']['service']['prefix_dir']}
                                         --with-libdir=#{lib_dir}
                                         --with-config-file-path=#{node['php']['conf_dir']}
                                         --with-config-file-scan-dir=#{node['php']['ext_conf_dir']}
                                         --without-pear
                                         --disable-ipv6
                                         --enable-pcntl
                                         --enable-fpm
                                         --with-fpm-user=#{node['php']['fpm_pool']['fpm_user']}
                                         --with-fpm-group=#{node['php']['fpm_pool']['fpm_group']}
                                         --enable-mbstring
                                         --enable-intl
                                         --enable-sysvmsg
                                         --enable-sysvsem
                                         --enable-sysvshm
                                         --enable-libxml
                                         --with-libxml-dir
                                         --enable-zip
                                         --with-zlib
                                         --with-openssl
                                         --with-kerberos
                                         --with-curl
                                         --enable-ftp
                                         --enable-exif
                                         --with-gd
                                         --enable-gd-native-ttf
                                         --with-gettext
                                         --with-gmp
                                         --with-mhash
                                         --with-mcrypt
                                         --with-iconv
                                         --enable-sockets
                                         --enable-soap
                                         --with-sqlite3
                                         --with-mysql=mysqlnd
                                         --with-mysqli=mysqlnd
                                         --with-pdo-mysql=mysqlnd
                                         --with-pdo-sqlite}

configure_options = configure_options.join(' ')
version = node['php']['service']['php_src_version']

remote_file "#{Chef::Config[:file_cache_path]}/php-#{version}.tar.gz" do
  source "#{node['php']['service']['php_src_url']}"
  checksum node['php']['service']['checksum']
  mode '0644'
  not_if "which #{node['php']['bin']}"
end

if node['php']['ext_dir']
  directory node['php']['ext_dir'] do
    owner 'root'
    group 'root'
    mode '0755'
    recursive true
  end
end

bash 'build php' do
  cwd Chef::Config[:file_cache_path]
  code <<-EOF
  tar -xf php-#{version}.tar.gz
  (cd php-#{version} && ./configure #{configure_options})
  (cd php-#{version} && make && make install)
  EOF
  not_if "which #{node['php']['bin']}"
end

directory node['php']['conf_dir'] do
  owner 'root'
  group 'root'
  mode '0755'
  recursive true
end

directory node['php']['ext_conf_dir'] do
  owner 'root'
  group 'root'
  mode '0755'
  recursive true
end

template "phpext.sh" do
	path "#{node['php']['service']['prefix_dir']}/bin/phpext.sh"
    owner 'root'
    group 'root'
    mode '0755'
end

node['php']['extensions'].each do |name,ext|
    ext_config = "#{node['php']['ext_conf_dir']}/#{name}.ini"
    if ext.enable

        if ext.has_key?('src_url')
            ext_so  = "`#node{['php']['service']['prefix_dir']}}/bin/php-config  --extension-dir`/#{name}.so"
            remote_file "#{Chef::Config[:file_cache_path]}/#{ext['src_package']}.tar.gz" do
              source "#{ext['src_url']}"
              mode '0644'
              not_if "which #{ext_so}"
            end

            bash "build php extension #{name}" do
              cwd Chef::Config[:file_cache_path]
              code <<-EOF
              tar -xf #{ext['src_package']}.tar.gz
              (cd #{ext['src_package']} && #{node['php']['service']['prefix_dir']}/bin/phpext.sh #{ext['src_options']} )
              (cd #{ext['src_package']} && make && make install)
              EOF
              not_if "which #{ext_so}"
            end

        end

        template "extension.ini" do
            path "#{ext_config}"
            owner 'root'
            group 'root'
            mode '0644'
            variables({
                :name => name,
                :ext => ext
            })
            not_if "which #{ext_config}"
        end

        puts ext_config
        puts ext
    else
        bash "remove php extension #{name}" do
          cwd Chef::Config[:file_cache_path]
          code <<-EOF
            mv #{ext_config} #{ext_config}_disable
          EOF
          not_if "which #{ext_config}"
        end
    end
end

include_recipe "php::ini"

