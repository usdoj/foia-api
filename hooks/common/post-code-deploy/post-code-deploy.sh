#!/bin/bash
#
# Cloud Hook: post-code-deploy
#
# The post-code-deploy hook is run whenever you use the Workflow page to
# deploy new code to an environment, either via drag-drop or by selecting
# an existing branch or tag from the Code drop-down list. See
# ../README.md for details.
#
# Usage: post-code-deploy site target-env source-branch deployed-tag repo-url
#                         repo-type

set -ev

site="$1"
target_env="$2"
source_branch="$3"
deployed_tag="$4"
repo_url="$5"
repo_type="$6"
drush_alias=$site'.'$target_env

repo_root="/var/www/html/$site.$target_env"
export PATH=$repo_root/vendor/bin:$PATH
cd $repo_root


drush @$drush_alias updb --no-interaction -v
drush @$drush_alias updb --no-interaction -v
drush @$drush_alias updatedb:status --no-interaction -v
drush @$drush_alias cache-rebuild --no-interaction -v
drush @$drush_alias config:import --no-interaction -v
drush @$drush_alias config:import --no-interaction -v
drush @$drush_alias cache-rebuild --no-interaction -v
drush @$drush_alias config:import --no-interaction -v
drush @$drush_alias config:import --no-interaction -v
drush @$drush_alias cache-rebuild --no-interaction -v
drush @$drush_alias config:status --no-interaction -v
drush @$drush_alias deploy:hook --no-interaction -v
drush @$drush_alias cache-rebuild --no-interaction -v

set +v
