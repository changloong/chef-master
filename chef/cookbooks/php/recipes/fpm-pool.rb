#
# Cookbook Name:: php
# Recipe:: fpm-pool

include_recipe "php::fpm"

directory "#{node[:php][:fpm_conf_dir]}/pool.d" do
    recursive true
    owner 'root'
    group 'root'
    mode '0744'
end

template "fpm-pool.conf" do
	path "#{node['php']['fpm_conf_dir']}/pool.d/#{node['php']['fpm_pool']['name']}.conf"
	unless platform?('windows')
		owner 'root'
		group 'root'
		mode '0644'
	end
end