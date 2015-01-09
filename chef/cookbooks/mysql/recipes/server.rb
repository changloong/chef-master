#
# Cookbook Name:: mysql
# Recipe:: server
#

include_recipe 'mysql::default'
include_recipe 'build-essential'

pkgs = value_for_platform_family(
  %w{ rhel fedora } => %w{ cmake ncurses-devel },
  %w{ debian ubuntu } => %w{ cmake },
  'default' => %w{ cmake }
  )

pkgs.each do |pkg|
  package pkg do
    action :install
  end
end

group "mysql" do
  gid 500
end

user "mysql" do
  uid 500
  gid "mysql"
  shell "/sbin/nologin"
end

configure_options = %W{-DCMAKE_INSTALL_PREFIX=#{node['mysql']['mysqld']['basedir']}
                         -DDEFAULT_CHARSET=#{node['mysql']['mysqld']['character_set_server']}
                         -DDEFAULT_COLLATION=#{node['mysql']['mysqld']['collation_server']}
                         -DMYSQL_TCP_PORT=#{node['mysql']['mysqld']['port']}
                         -DMYSQL_UNIX_ADDR=#{node['mysql']['mysqld']['basedir']}/data/mysql.sock
                         -DMYSQL_DATADIR=#{node['mysql']['mysqld']['basedir']}/data
                         -DSYSCONFDIR=#{node['mysql']['mysqld']['basedir']}/etc
                         -DWITH_INNOBASE_STORAGE_ENGINE=1
                         -DWITH_MYISAM_STORAGE_ENGINE=1
                         -DWITH_READLINE=1
                         -DWITH_EXTRA_CHARSETS=all}

configure_options = configure_options.join(' ')
mysql_package = node['mysql']['service']['src_package']

remote_file "#{Chef::Config[:file_cache_path]}/#{mysql_package}.tar.gz" do
  source "#{node['mysql']['service']['src_url']}"
  checksum node['mysql']['service']['checksum']
  mode '0644'
  not_if "which #{node['mysql']['service']['mysqld_bin']}"
end

bash 'build mysql' do
  cwd Chef::Config[:file_cache_path]
  code <<-EOF
  tar -xf #{mysql_package}.tar.gz
  (cd #{mysql_package} && cmake #{configure_options} )
  (cd #{mysql_package} && make && make install)
  EOF
  not_if "which #{node['mysql']['service']['mysqld_bin']}"
end


directory "#{node[:mysql][:service][:dir]}" do
    recursive true
	unless platform?('windows')
        owner node[:mysql][:mysqld][:user]
        group node[:mysql][:mysqld][:group]
		mode '0744'
	end
end

directory "#{node[:mysql][:service][:etc_dir]}" do
    recursive true
	unless platform?('windows')
        owner node[:mysql][:mysqld][:user]
        group node[:mysql][:mysqld][:group]
		mode '0744'
	end
end

directory "#{node[:mysql][:service][:wrap_bin_dir]}" do
    recursive true
	unless platform?('windows')
		owner 'root'
		group 'root'
		mode '0744'
	end
end

directory "#{node[:mysql][:mysqld][:basedir]}" do
    recursive true
	unless platform?('windows')
		owner node[:mysql][:mysqld][:user]
		group node[:mysql][:mysqld][:group]
		mode '0744'
	end
end

directory "#{node[:mysql][:mysqld][:datadir]}" do
    recursive true
	unless platform?('windows')
		owner node[:mysql][:mysqld][:user]
		group node[:mysql][:mysqld][:group]
		mode '0744'
	end
end

directory "#{node[:mysql][:mysqld][:tmpdir]}" do
    recursive true
	unless platform?('windows')
		owner node[:mysql][:mysqld][:user]
		group node[:mysql][:mysqld][:group]
		mode '0744'
	end
end

directory "#{node[:mysql][:mysqld][:logdir]}" do
    recursive true
	unless platform?('windows')
		owner node[:mysql][:mysqld][:user]
		group node[:mysql][:mysqld][:group]
		mode '0744'
	end
end

mysqld_initd="/etc/init.d/#{node[:mysql][:service][:service_name]}"
template "initd" do
	path "#{mysqld_initd}"
    owner 'root'
    group 'root'
    mode '0555'
end

template "my.cnf" do
	path "#{node[:mysql][:service][:etc_dir]}/my.cnf"
    owner node[:mysql][:mysqld][:user]
    group node[:mysql][:mysqld][:group]
    mode '0644'
end

bash 'touch mysqld.cnf' do
  cwd Chef::Config[:file_cache_path]
  code <<-EOF
    touch #{node[:mysql][:service][:etc_dir]}/mysqld.cnf
  EOF
  not_if "which #{node[:mysql][:service][:etc_dir]}/mysqld.cnf"
end

template "wrap_mysql.sh" do
	path "#{node[:mysql][:service][:wrap_bin_dir]}/mysql.sh"
    owner 'root'
    group 'root'
    mode '0555'
end

template "wrap_mysqldump.sh" do
	path "#{node[:mysql][:service][:wrap_bin_dir]}/mysqldump.sh"
    owner 'root'
    group 'root'
    mode '0555'
end

template "wrap_mysqld.sh" do
	path "#{node[:mysql][:service][:wrap_bin_dir]}/mysqld.sh"
    owner 'root'
    group 'root'
    mode '0555'
end

template "wrap_mysqld_safe.sh" do
	path "#{node[:mysql][:service][:wrap_bin_dir]}/mysqld_safe.sh"
    owner 'root'
    group 'root'
    mode '0555'
end

template "wrap_mysqladmin.sh" do
	path "#{node[:mysql][:service][:wrap_bin_dir]}/mysqladmin.sh"
    owner 'root'
    group 'root'
    mode '0555'
end

template "wrap_shutdown.sh" do
	path "#{node[:mysql][:service][:wrap_bin_dir]}/shutdown.sh"
    owner 'root'
    group 'root'
    mode '0555'
end

template "wrap_mysql_install_db" do
	path "#{node[:mysql][:service][:wrap_bin_dir]}/mysql_install_db.sh"
    owner 'root'
    group 'root'
    mode '0755'
end

bash 'mysql_install_db' do
  cwd Chef::Config[:file_cache_path]
  code <<-EOF
    sh #{node[:mysql][:service][:wrap_bin_dir]}/mysql_install_db.sh
  EOF
  not_if "ls #{node[:mysql][:mysqld][:datadir]}/mysql/user.frm"
end
