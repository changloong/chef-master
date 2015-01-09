#
# Cookbook Name:: nginx
# Recipe:: source

include_recipe 'build-essential'
#include_recipe 'xml'
include_recipe 'yum-epel' if node['platform_family'] == 'rhel'


pkgs = value_for_platform_family(
  %w{ rhel fedora } => %w{ openssl-devel GeoIP-devel zlib-devel pcre-devel },
  %w{ debian ubuntu } => %w{ libssl-dev },
  'default' => %w{ libssl-dev }
  )

pkgs.each do |pkg|
  package pkg do
    action :install
  end
end

group "nginx" do
  gid 498
end

user "nginx" do
  uid 498
  gid "nginx"
  shell "/sbin/nologin"
end

zlib_path="/usr"
zlib_asm_path="/usr"

configure_options = %W{--prefix=#{node['nginx']['service']['prefix_dir']}
                       --conf-path=#{node['nginx']['service']['etc_dir']}
                       --error-log-path=#{node['nginx']['service']['log_dir']}/error.log
                       --http-log-path=#{node['nginx']['service']['log_dir']}/access.log
                       --http-client-body-temp-path=#{node['nginx']['service']['tmp_dir']}/nginx_client_body
                       --http-proxy-temp-path=#{node['nginx']['service']['tmp_dir']}/nginx_proxy
                       --http-fastcgi-temp-path=#{node['nginx']['service']['tmp_dir']}/nginx_fastcgi
                       --pid-path=#{node['nginx']['service']['pid']}
                       --lock-path=#{node['nginx']['service']['tmp_dir']}/nginx.lock
                       --user=#{node['nginx']['service']['user']}
                       --group=#{node['nginx']['service']['group']}
                       --without-http_uwsgi_module
                       --without-http_scgi_module
                       --with-file-aio
                       --with-http_ssl_module
                       --with-http_spdy_module
                       --with-http_addition_module
                       --with-http_gunzip_module
                       --with-http_gzip_static_module
                       --with-http_auth_request_module
                       --with-http_geoip_module
                       --with-http_sub_module
                       --with-http_stub_status_module
                       --with-pcre
                       --with-pcre-jit
                       --with-md5-asm
                       --with-sha1-asm}

configure_options = configure_options.join(' ')
nginx_src_package = node['nginx']['service']['nginx_src_package']

remote_file "#{Chef::Config[:file_cache_path]}/#{nginx_src_package}.tar.gz" do
  source "#{node['nginx']['service']['nginx_src_url']}"
  checksum node['nginx']['service']['nginx_src_checksum']
  mode '0644'
  not_if "which #{node['nginx']['service']['bin']}"
end

bash 'build nginx' do
  cwd Chef::Config[:file_cache_path]
  code <<-EOF
  tar -xf #{nginx_src_package}.tar.gz
  (cd #{nginx_src_package} && ./configure #{configure_options})
  (cd #{nginx_src_package} && make && make install)
  EOF
  not_if "which #{node['nginx']['service']['bin']}"
end
