#!/usr/bin/env bash

# If not in the `dev-workspace` directory, change to it
if [[ ! $(pwd) =~ .*dev-workspace$ ]]; then
  cd dev-workspace
fi

if [[ ! -f ../.env ]]; then
  echo "Error: .env file not found in the root directory. Please run 'cp .env.example .env' to create it."
  exit 1
fi

set -a
source ../.env
set +a

sh ./scripts/terminal-service-stop.sh
