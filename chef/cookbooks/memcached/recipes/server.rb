#
# Cookbook Name:: memcached
# Recipe:: server
#

include_recipe 'memcached::default'
include_recipe 'build-essential'

pkgs = value_for_platform_family(
  %w{ rhel fedora } => %w{ libevent-devel },
  %w{ debian ubuntu } => %w{ libevent-devel },
  'default' => %w{ libevent-devel }
  )

pkgs.each do |pkg|
  package pkg do
    action :install
  end
end

configure_options = %W{--prefix=#{node['memcached']['service']['dir']}
        --disable-sasl
        --disable-coverage
        --disable-docs
        --enable-64bit}

configure_options = configure_options.join(' ')
src_package = node['memcached']['service']['src_package']

remote_file "#{Chef::Config[:file_cache_path]}/#{src_package}.tar.gz" do
  source "#{node['memcached']['service']['src_url']}"
  mode '0644'
  not_if "which #{node['memcached']['service']['bin']}"
end

bash 'build memcached' do
  cwd Chef::Config[:file_cache_path]
  code <<-EOF
  tar -xf #{src_package}.tar.gz
  (cd #{src_package} && ./configure #{configure_options} )
  (cd #{src_package} && make && make install)
  EOF
  not_if "which #{node['memcached']['service']['bin']}"
end

configure_options = %W{--prefix=#{node['memcached']['service']['dir']}
        --with-memcached=#{node['memcached']['service']['dir']}
        --disable-sasl}

configure_options = configure_options.join(' ')
src_package = node['memcached']['service']['libmemcached_src_package']

libmemcached_so = "#{node['memcached']['service']['dir']}/lib/libmemcached.so"
remote_file "#{Chef::Config[:file_cache_path]}/#{src_package}.tar.gz" do
  source "#{node['memcached']['service']['libmemcached_src_url']}"
  mode '0644'
  not_if "which #{libmemcached_so}"
end

bash 'build libmemcached' do
  cwd Chef::Config[:file_cache_path]
  code <<-EOF
  tar -xf #{src_package}.tar.gz
  (cd #{src_package} && ./configure #{configure_options} )
  (cd #{src_package} && make && make install)
  EOF
  not_if "which #{libmemcached_so}"
end

directory "#{node[:memcached][:service][:dir]}" do
    recursive true
    owner node[:memcached][:service][:user]
end

directory "#{node[:memcached][:service][:etc_dir]}" do
    recursive true
    owner node[:memcached][:service][:user]
end

directory "#{node[:memcached][:service][:datadir]}" do
    recursive true
    owner node[:memcached][:service][:user]
end

directory "#{node[:memcached][:service][:tmpdir]}" do
    recursive true
    owner node[:memcached][:service][:user]
end

directory "#{node[:memcached][:service][:logdir]}" do
    recursive true
    owner node[:memcached][:service][:user]
end

service_initd="/etc/init.d/#{node[:memcached][:service][:name]}"
template "initd" do
	path "#{service_initd}"
    owner 'root'
    group 'root'
    mode '0555'
end