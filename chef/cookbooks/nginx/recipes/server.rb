#
# Cookbook Name:: nginx
# Recipe:: server

include_recipe 'nginx::default'

puts "====================== install nginx ====================== "
puts node[:nginx]

include_recipe 'nginx::source'
include_recipe 'nginx::site'

service 'nginx' do
  supports :status => true, :restart => true, :reload => true
  action   :start
end