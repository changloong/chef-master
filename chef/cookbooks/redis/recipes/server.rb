#
# Cookbook Name:: redis
# Recipe:: server
#

include_recipe 'redis::default'
include_recipe 'build-essential'

src_package = node['redis']['service']['src_package']
src_prefix = node['redis']['service']['dir']

remote_file "#{Chef::Config[:file_cache_path]}/#{src_package}.tar.gz" do
  source "#{node['redis']['service']['src_url']}"
  mode '0644'
  not_if "which #{node['redis']['service']['bin']}"
end

bash 'build redis' do
  cwd Chef::Config[:file_cache_path]
  code <<-EOF
  tar -xf #{src_package}.tar.gz
  (cd #{src_package} && make PREFIX=#{src_prefix} install)
  EOF
  not_if "which #{node['redis']['service']['bin']}"
end

directory "#{node[:redis][:service][:dir]}" do
    recursive true
    owner node[:redis][:service][:user]
end

directory "#{node[:redis][:service][:etc_dir]}" do
    recursive true
    owner node[:redis][:service][:user]
end

directory "#{node[:redis][:service][:datadir]}" do
    recursive true
    owner node[:redis][:service][:user]
end

directory "#{node[:redis][:service][:tmpdir]}" do
    recursive true
    owner node[:redis][:service][:user]
end

directory "#{node[:redis][:service][:logdir]}" do
    recursive true
    owner node[:redis][:service][:user]
end

service_initd="/etc/init.d/#{node[:redis][:service][:name]}"
template "initd" do
	path "#{service_initd}"
    owner 'root'
    group 'root'
    mode '0555'
end

template "redis.conf" do
	path "#{node['redis']['service']['etc_dir']}/redis.conf"
    owner node[:redis][:service][:user]
    mode '0644'
end
