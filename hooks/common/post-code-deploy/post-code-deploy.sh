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

# Prep for BLT commands.
repo_root="/var/www/html/$site.$target_env"
export PATH=$repo_root/vendor/bin:$PATH
cd $repo_root

blt artifact:ac-hooks:post-code-deploy $site $target_env $source_branch $deployed_tag $repo_url $repo_type --environment=$target_env -v --no-interaction -D drush.ansi=false

# Copy the PHPDocX library into the files directory so we can symlink it to
# docroot/libraries. The $repo_root/acquia-files/files-private/.htaccess file
# has already been copied into the library, so it will not be downloadable.
cp -r ~/phpdocx "$repo_root/docroot/sites/default/files/"

set +v
