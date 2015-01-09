#
# Cookbook Name:: build-essential
# Recipe:: default

require 'chef/shell_out'

case node['os']
when "linux"
  packages = value_for_platform(
    ["ubuntu", "debian"] => {
      "default" => ["build-essential", "binutils-doc"]
    },
    ["centos", "redhat", "fedora", "amazon"] => {
      "default" => ["gcc", "gcc-c++", "make"]
    }
  )

  packages.each do |pkg|
    package pkg do
      action :install
    end
  end

  %w{autoconf flex bison}.each do |pkg|
    package pkg do
      action :install
    end
  end
when "darwin"
  result = Chef::ShellOut.new("pkgutil --pkgs").run_command
  installed = result.stdout.split("\n").include?("com.apple.pkg.gcc4.2Leo")
  pkg_filename = File.basename(node['build_essential']['osx']['gcc_installer_url'])
  pkg_path = "#{Chef::Config[:file_cache_path]}/#{pkg_filename}"

  remote_file pkg_path do
    source node['build_essential']['osx']['gcc_installer_url']
    checksum node['build_essential']['osx']['gcc_installer_checksum']
    not_if { installed }
  end

  execute "sudo installer -pkg \"#{pkg_path}\" -target /" do
    not_if { installed }
  end
end
