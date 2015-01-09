#
# Cookbook Name:: php
# Recipe:: ini
#

template "#{node['php']['conf_dir']}/php.ini" do
	source "php.ini.erb"
	unless platform?('windows')
		owner 'root'
		group 'root'
		mode '0644'
	end
	variables(:directives => node['php']['directives'])
end

template "#{node['php']['fpm_conf_dir']}/php.ini" do
	source "php.ini.erb"
	unless platform?('windows')
		owner 'root'
		group 'root'
		mode '0644'
	end
	variables(:directives => node['php']['directives'])
end
