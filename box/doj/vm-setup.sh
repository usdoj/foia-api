#!/bin/bash

# Create and configure the Drupal files directories.
DRUPAL_BASIC_SETUP_FILE=/etc/doj_basic_setup_complete

# Check to see if we've already performed this setup.
if [ ! -e "$DRUPAL_BASIC_SETUP_FILE" ]; then

  # Clone our repos.
  cd /var/www
  sudo chmod 777 .
  git clone https://github.com/usdoj/foia-api.git
  git clone https://github.com/usdoj/foia.gov.git
  # Rename the API repo to match the way it is used in various files: dojfoia.
  mv foia-api foia

  # Collect Github info.
  echo "Github email address?"
  read github_email
  echo "Github user name?"
  read github_user
  echo "Full name?"
  read full_name

  # Git config and aliases.
  git config --global user.name "$full_name"
  git config --global user.email "$github_email"
  git config --global alias.co checkout
  git config --global alias.br branch
  git config --global alias.ci commit
  git config --global alias.di diff
  git config --global alias.st status

  # Back-stage installation.
  cd /var/www/foia
  git remote add fork https://github.com/$github_user/foia-api.git
  composer install

  # Front-stage installation.
  # Includes a manual Ruby installation because I can't get DrupalVM to set
  # a specific version of Ruby.
  gpg --keyserver hkp://keys.gnupg.net --recv-keys 409B6B1796C275462A1703113804BB82D39DC0E3 7D2BAF1CF37B13E2069D6956105BD0E739499BDB
  \curl -sSL https://get.rvm.io | bash -s stable
  source /home/vagrant/.rvm/scripts/rvm
  rvm install "ruby-2.3.4"
  cd /var/www/foia.gov
  git remote add fork https://github.com/$github_user/foia.gov.git
  gem install bundler
  bundle install
  npm install
  NODE_ENV=local APP_ENV=local make build

  echo "****"
  echo "Local codebase installed."
  echo "Please run 'drush sql-sync @foia.test @foia.local' to set up your local database."
  echo "****"

  # Make the drush and blt commands global.
  echo 'alias drush="/var/www/foia/vendor/bin/drush"' >> ~/.bashrc
  echo 'alias blt="/var/www/foia/vendor/bin/blt"' >> ~/.bashrc

  # Run a few BLT setup commands.
  /var/www/foia/vendor/bin/blt setup:settings
  /var/www/foia/vendor/bin/blt setup:build
  /var/www/foia/vendor/bin/blt setup:hash-salt

  # Create a file to indicate this script has already run.
  sudo touch $DRUPAL_BASIC_SETUP_FILE
else
  exit 0
fi
