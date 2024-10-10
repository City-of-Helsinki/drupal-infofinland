#!/bin/bash

while true
do
  echo "Copying paragraphs: $(date +'%Y-%m-%dT%H:%M:%S%:z')\n"
  drush drush:queue-run paragraph_copy_worker --time-limit=60 --lease-time=60 --items-limit=3
  sleep 60
done
