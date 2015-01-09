#
# Cookbook Name:: build-essential
# Attributes:: default

case platform
when "mac_os_x"
  case
  when Chef::VersionConstraint.new("~> 10.7.0").include?(platform_version)
    default['build_essential']['osx']['gcc_installer_url'] = "https://github.com/downloads/kennethreitz/osx-gcc-installer/GCC-10.7-v2.pkg"
    default['build_essential']['osx']['gcc_installer_checksum'] = "df36aa87606feb99d0db9ac9a492819e"
  when Chef::VersionConstraint.new("~> 10.6.0").include?(platform_version)
    default['build_essential']['osx']['gcc_installer_url'] = "https://github.com/downloads/kennethreitz/osx-gcc-installer/GCC-10.6.pkg"
    default['build_essential']['osx']['gcc_installer_checksum'] = "d1db5bab6a3f6b9f3b5577a130baeefa"
  end
end
