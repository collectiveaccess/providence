# -*- mode: ruby -*-
# vi: set ft=ruby :

# All Vagrant configuration is done below. The "2" in Vagrant.configure
# configures the configuration version (we support older styles for
# backwards compatibility). Please don't change it unless you know what
# you're doing.
Vagrant.configure(2) do |config|
  # The most common configuration options are documented and commented below.
  # For a complete reference, please see the online documentation at
  # https://docs.vagrantup.com.

  # Every Vagrant development environment requires a box. You can search for
  # boxes at https://atlas.hashicorp.com/search.
  config.vm.box = "ubuntu/trusty64"

  # Disable automatic box update checking. If you disable this, then
  # boxes will only be checked for updates when the user runs
  # `vagrant box outdated`. This is not recommended.
  # config.vm.box_check_update = false

  # Create a forwarded port mapping which allows access to a specific port
  # within the machine from a port on the host machine. In the example below,
  # accessing "localhost:8080" will access port 80 on the guest machine.
  config.vm.network "forwarded_port", guest: 80, host: 8080

  # Create a private network, which allows host-only access to the machine
  # using a specific IP.
  # config.vm.network "private_network", ip: "192.168.33.10"

  # Create a public network, which generally matched to bridged network.
  # Bridged networks make the machine appear as another physical device on
  # your network.
  # config.vm.network "public_network"

  # Share an additional folder to the guest VM. The first argument is
  # the path on the host to the actual folder. The second argument is
  # the path on the guest to mount the folder. And the optional third
  # argument is a set of non-required options.
  # config.vm.synced_folder "../data", "/vagrant_data"

  # Provider-specific configuration so you can fine-tune various
  # backing providers for Vagrant. These expose provider-specific options.
  # Example for VirtualBox:
  #
  config.vm.provider "virtualbox" do |v|
  	v.memory = "1024"
  	v.cpus = 1
  	v.name = "collectiveaccess"
  end

  #
  # View the documentation for the provider you are using for more
  # information on available options.

  # Define a Vagrant Push strategy for pushing to Atlas. Other push strategies
  # such as FTP and Heroku are also available. See the documentation at
  # https://docs.vagrantup.com/v2/push/atlas.html for more information.
  # config.push.define "atlas" do |push|
  #   push.app = "YOUR_ATLAS_USERNAME/YOUR_APPLICATION_NAME"
  # end

  # Enable provisioning with a shell script. Additional provisioners such as
  # Puppet, Chef, Ansible, Salt, and Docker are also available. Please see the
  # documentation for more information about their specific syntax and use.
  config.vm.provision "shell", inline: <<-SHELL

  	setup_php="/vagrant/setup.php"

	add-apt-repository ppa:kirillshkrogalev/ffmpeg-next
  	apt-get update
    apt-get -q -y -o Dpkg::Options::=--force-confold upgrade

  	if [[ -e /var/lock/vagrant-provision ]]; then
        exit;
    fi

    echo "mysql-server mysql-server/root_password password root" | sudo debconf-set-selections
    echo "mysql-server mysql-server/root_password_again password root" | sudo debconf-set-selections
    apt-get -y install mysql-client mysql-server

    apt-get -q -y -o Dpkg::Options::=--force-confold install apache2
    apt-get -q -y -o Dpkg::Options::=--force-confold install php5 libapache2-mod-php5 php5-cli
    apt-get -q -y -o Dpkg::Options::=--force-confold install php5-curl php5-mysqlnd php5-json php5-gd php5-imap php5-mcrypt
    apt-get -q -y -o Dpkg::Options::=--force-confold install htop screen vim apachetop vnstat git
    apt-get -q -y -o Dpkg::Options::=--force-confold install ffmpeg graphicsmagick python-pdfminer
    apt-get -q -y -o Dpkg::Options::=--force-confold install ghostscript dcraw xpdf mediainfo exiftool

    # slooooow setup with gmagick and libreoffice (that install takes forever). if you want a shiny setup, uncomment it here
    #
    # apt-get -q -y -o Dpkg::Options::=--force-confold install php5-dev php-pear libgraphicsmagick1-dev libreoffice abiword
	# pecl install gmagick-1.1.7RC3
	#
	# cat << EOF > /etc/php5/mods-available/gmagick.ini
    # extension=gmagick.so
    # EOF
	#
	# ln -s /etc/php5/mods-available/gmagick.ini /etc/php5/apache2/conf.d/20-gmagick.ini

    echo "CREATE DATABASE IF NOT EXISTS collectiveaccess" | mysql -u root --password=root

    if ! [ -L /var/www/html ]; then
      rm -rf /var/www/html
      ln -fs /vagrant /var/www/html
    fi

    cp /vagrant/setup.php-dist /vagrant/setup.php
    sed -i "s/my_database_user/root/g" ${setup_php}
    sed -i "s/my_database_password/root/g" ${setup_php}
    sed -i "s/name_of_my_database/collectiveaccess/g" ${setup_php}

    chown -R www-data /vagrant/app/tmp
    chown -R www-data /vagrant/web/vendor/ezyang/htmlpurifier/library/HTMLPurifier/DefinitionCache/Serializer
    chown -R www-data /vagrant/media/collectiveaccess

    service apache2 restart
    service mysql restart

    touch /var/lock/vagrant-provision

  SHELL
end
