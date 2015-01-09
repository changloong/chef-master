#
# Cookbook Name:: php
# Recipe:: fpm

include_recipe "php::source"

directory "#{node[:php][:fpm_log_dir]}" do
    recursive true
	unless platform?('windows')
		owner 'root'
		group 'root'
		mode '0744'
	end
end

directory "#{node[:php][:service][:tmp_dir]}" do
    recursive true
    owner node['php']['fpm_pool']['user']
    group node['php']['fpm_pool']['group']
    mode '0744'
end

template "php-fpm.conf" do
	path "#{node['php']['fpm_conf_dir']}/php-fpm.conf"
	unless platform?('windows')
		owner 'root'
		group 'root'
		mode '0644'
	end
end

template "initd" do
	path "/etc/init.d/php-fpm"
	unless platform?('windows')
		owner 'root'
		group 'root'
		mode '0755'
	end
end

include_recipe "php::fpm-pool"

include_recipe "php::ini"

service 'php-fpm' do
  supports :status => true, :restart => true, :reload => true
  action   :restart
end