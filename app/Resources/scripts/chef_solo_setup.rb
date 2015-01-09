
workdir = File.absolute_path( File.join( File.dirname(__FILE__), '..' ) )

cookbook_path [
                File.join(workdir, "cookbooks/site-cookbooks"),
                File.join(workdir, "cookbooks/cookbooks"),
              ]

file_store_path File.join(workdir, 'cache')
file_cache_path File.join(workdir, 'cache')
data_bag_path File.join(workdir, 'data_bags')
role_path   File.join(workdir, 'roles')


{% if debug %}
log_level :debug
{% endif %}
Chef::Log::Formatter.show_time = false
Chef::Config.ssl_verify_mode = :verify_peer