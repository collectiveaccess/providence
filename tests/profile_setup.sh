#!/usr/bin/env bash

# Set environment variables
export PROFILE=${1:-testing}
export COLLECTIVEACCESS_HOME="$(dirname $(dirname "$0"))"
export PATH="$PATH:$COLLECTIVEACCESS_HOME/support/bin"

# Install the testing profile
"$COLLECTIVEACCESS_HOME"/support/bin/caUtils install --hostname=localhost --setup="tests/setup-tests.php" \
  --skip-roles --profile-name="$PROFILE" --admin-email=support@collectiveaccess.org
