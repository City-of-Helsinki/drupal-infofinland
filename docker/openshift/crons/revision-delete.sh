#!/bin/bash

while true
do
  echo "Running node-revision-delete:queue : $(date)"
  drush node-revision-delete:queue --no-interaction --finish
  # Sleep for 48 hours.
  sleep 172800
done
