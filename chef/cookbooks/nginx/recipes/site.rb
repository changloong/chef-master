#
# Cookbook Name:: nginx
# Recipe:: site


directory "#{node[:nginx][:site][:root]}" do
    recursive true
end

template "site" do
  path "#{node[:nginx][:service][:conf_dir]}/#{node[:nginx][:site][:name]}.conf"
  mode 644
end

bash 'touch #{node[:nginx][:service][:conf_dir]}/#{node[:nginx][:site][:name]}.config' do
  cwd Chef::Config[:file_cache_path]
  code <<-EOF
    touch #{node[:nginx][:service][:conf_dir]}/#{node[:nginx][:site][:name]}.config
  EOF
  not_if "which #{node[:nginx][:service][:conf_dir]}/#{node[:nginx][:site][:name]}.config"
end

include_recipe 'nginx::server'