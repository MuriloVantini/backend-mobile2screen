#!/bin/sh

set -eu

exec /usr/bin/supervisord -c /app/docker/supervisord.conf
