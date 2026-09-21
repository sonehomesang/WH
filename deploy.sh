#!/usr/bin/env bash
# WH one-command deploy — run on the host:  bash deploy.sh
# Pulls the latest committed CODE from GitHub and clears Laravel caches.
# Only touches code dirs — never .env, storage, vendor or node_modules.
set -e

cd /home/wh.namtheun2.com/public_html
PHP=/usr/local/lsws/lsphp83/bin/php

echo "→ fetching latest from GitHub…"
git fetch origin

echo "→ updating code files…"
git checkout origin/main -- app bootstrap config database lang public resources routes composer.json composer.lock artisan deploy.sh

echo "→ clearing caches…"
$PHP artisan view:clear
$PHP artisan config:clear
$PHP artisan route:clear

echo "✓ deployed $(git rev-parse --short origin/main) at $(date '+%F %T')"
echo "  (if composer.json changed, also run: composer install --no-dev -o)"
