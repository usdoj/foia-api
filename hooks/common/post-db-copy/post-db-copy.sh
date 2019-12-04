#!/bin/sh
#
# Cloud Hook: post-db-copy
#
# The post-db-copy hook is run whenever you use the Workflow page to copy a
# database from one environment to another.
#
# Usage: post-db-copy site target-env db-name source-env

set -ev

site="$1"
target_env="$2"
db_name="$3"
source_env="$4"

# Prep for BLT commands.
repo_root="/var/www/html/$site.$target_env"
export PATH=$repo_root/vendor/bin:$PATH
cd $repo_root

blt artifact:ac-hooks:post-db-copy $site $target_env $db_name $source_env --environment=$target_env -v --no-interaction -D drush.ansi=false

echo "$site.$target_env: Received copy of database $db_name from $source_env."

# Only do this when going from prod to something other than prod.
if [ "$source_env" = "prod" ] && [ "$target_env" != "prod" ]
then
echo "$site.$target_env: Sanitizing database $db_name on $target_env."
# Delete all foia_request entities.
# For some reason the entity:delete approach is not working.
# So instead truncate all the tables.
(cat <<EOF
TRUNCATE foia_request;
TRUNCATE foia_request__field_agency_component;
TRUNCATE foia_request__field_case_management_id;
TRUNCATE foia_request__field_error_code;
TRUNCATE foia_request__field_error_description;
TRUNCATE foia_request__field_error_message;
TRUNCATE foia_request__field_requester_email;
TRUNCATE foia_request__field_response_code;
TRUNCATE foia_request__field_submission_failures;
TRUNCATE foia_request__field_submission_method;
TRUNCATE foia_request__field_submission_time;
TRUNCATE foia_request__field_tracking_number;
TRUNCATE foia_request__field_webform_submission_id;
EOF
) | drush @$site.$target_env sql-cli
# Eventually the following might probably be better:
#drush @$site.$target_env entity:delete foia_request

# Clear any queues that may be in progress.
drush @$site.$target_env sqlq "TRUNCATE queue"

# Delete all webform_submission entities.
drush @$site.$target_env entity:delete webform_submission
fi

set +v
