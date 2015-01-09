#
# Cookbook Name:: php
# Recipe:: default


directory "#{node['php']['conf_dir']}" do
    recursive true
	unless platform?('windows')
		owner 'root'
		group 'root'
		mode '0744'
	end
end
