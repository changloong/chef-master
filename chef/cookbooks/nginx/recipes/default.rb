#
# Cookbook Name:: nginx
# Recipe:: default


template "initd" do
  path "/etc/init.d/nginx"
  mode 755
end

directory "#{node[:nginx][:service][:etc_dir]}" do
    recursive true
    owner node[:nginx][:service][:user]
    group node[:nginx][:service][:group]
end

directory "#{node[:nginx][:service][:conf_dir]}" do
    recursive true
    owner node[:nginx][:service][:user]
    group node[:nginx][:service][:group]
end

directory "#{node[:nginx][:service][:log_dir]}" do
    recursive true
    owner node[:nginx][:service][:user]
    group node[:nginx][:service][:group]
end

directory "#{node[:nginx][:service][:tmp_dir]}" do
    recursive true
    owner node[:nginx][:service][:user]
    group node[:nginx][:service][:group]
end

template "nginx.conf" do
  path "#{node[:nginx][:service][:etc_dir]}/nginx.conf"
  owner 'root'
  group 'root'
  mode 644
end

template "fastcgi.conf" do
  path "#{node[:nginx][:service][:etc_dir]}/fastcgi.conf"
    owner 'root'
    group 'root'
  mode 644
end

template "mime.types" do
  path "#{node[:nginx][:service][:etc_dir]}/mime.types"
  owner 'root'
  group 'root'
  mode 644
end