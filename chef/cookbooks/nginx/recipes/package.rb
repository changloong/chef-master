#
# Cookbook Name:: nginx
# Recipe:: package


if platform_family?('rhel')
  if node['nginx']['repo_source'] == 'epel'
    include_recipe 'yum-epel'
  elsif node['nginx']['repo_source'] == 'nginx'
    include_recipe 'nginx::repo'
    package_install_opts = '--disablerepo=* --enablerepo=nginx'
  elsif node['nginx']['repo_source'].nil?
    log "node['nginx']['repo_source'] was not set, no additional yum repositories will be installed." do
      level :debug
    end
  else
    fail ArgumentError, "Unknown value '#{node['nginx']['repo_source']}' was passed to the nginx cookbook."
  end
elsif platform_family?('debian')
  include_recipe 'nginx::repo'
end

package node['nginx']['package_name'] do
  options package_install_opts
  action :install
  not_if 'which nginx'
end