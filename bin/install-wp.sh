#!/usr/bin/env sh

# Install WordPress.
wp core install \
  --title="Damn Vulnerable WordPress" \
  --admin_user="admin" \
  --admin_password="admin" \
  --admin_email="admin@example.com" \
  --url="http://127.0.0.1:31337/" \
  --skip-email

# Update permalink structure.
# wp option update permalink_structure "/%year%/%monthnum%/%postname%/" --skip-themes --skip-plugins

# Activate plugin.
wp plugin activate iwp-client 
wp plugin activate social-warfare 
wp plugin activate wp-advanced-search 
wp plugin activate wp-file-upload 
# wp plugin activate wp-time-capsule # Causes error

# Update DB
wp db import dump.sql

