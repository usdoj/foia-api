#!/bin/sh
#
# Cloud Hook: post-files-copy
#
# The post-files-copy hook is run whenever you use the Workflow page to
# copy the files directory from one environment to another.
#
# Usage: post-files-copy site target-env source-env

set -ev

site="$1"
target_env="$2"
source_env="$3"

# Prep for BLT commands.
repo_root="/var/www/html/$site.$target_env"
export PATH=$repo_root/vendor/bin:$PATH
cd $repo_root

blt artifact:ac-hooks:post-files-copy $site $target_env $source_env --environment=$target_env -v --no-interaction -D drush.ansi=false

echo "$site.$target_env: Received copy of files from $source_env."

# Only do this when going from prod to something other than prod.
if [ "$source_env" = "prod" ] && [ "$target_env" != "prod" ]
then
echo "$site.$target_env: Deleting webform uploads on $target_env."
drush @$site.$target_env eval "file_unmanaged_delete_recursive('private://webform')"
fi

set +v
