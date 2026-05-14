#!/bin/sh
#
# db-copy Cloud hook: db-scrub
#
# Scrub important information from a Drupal database.
#
# Usage: db-scrub.sh site target-env db-name source-env

set -ev

site="$1"
target_env="$2"
db_name="$3"
source_env="$4"

drush_alias=$site'.'$target_env

repo_root="/var/www/html/$site.$target_env"
export PATH=$repo_root/vendor/bin:$PATH
cd $repo_root

password=$(tr -dc 'A-Za-z0-9!?%=' < /dev/urandom | head -c 10)

drush @$drush_alias sql-sanitize --sanitize-password="${password}" --yes
drush @$drush_alias cr

set +v
