#!/usr/bin/env bash
set -euo pipefail

if command -v mariadb >/dev/null 2>&1 && mariadb-admin ping >/dev/null 2>&1; then
  :
elif command -v systemctl >/dev/null 2>&1; then
  systemctl enable --now mariadb 2>/dev/null || systemctl start mariadb 2>/dev/null || true
fi

if ! command -v mariadb >/dev/null 2>&1; then
  echo "MariaDB client not installed; install mariadb-server on the host" >&2
  exit 1
fi

mariadb <<'SQL'
CREATE DATABASE IF NOT EXISTS statsreg_test
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'statsreg'@'127.0.0.1' IDENTIFIED BY 'statsreg';
CREATE USER IF NOT EXISTS 'statsreg'@'localhost' IDENTIFIED BY 'statsreg';
GRANT ALL PRIVILEGES ON statsreg_test.* TO 'statsreg'@'127.0.0.1';
GRANT ALL PRIVILEGES ON statsreg_test.* TO 'statsreg'@'localhost';
FLUSH PRIVILEGES;
SQL
