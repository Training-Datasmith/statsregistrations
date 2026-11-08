#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TESTS_DIR="$ROOT/tests"

if [[ -n "${PHPUNIT_PHAR:-}" && -f "${PHPUNIT_PHAR}" && -x "$(command -v php 2>/dev/null || true)" ]]; then
  PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
  if [[ "$PHP_VER" == "5.6" ]]; then
    exec php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 "$TESTS_DIR/run-suite.php"
  fi
fi

USE_SUDO_DOCKER="${USE_SUDO_DOCKER:-0}"
DOCKER_BIN="${DOCKER_BIN:-docker}"
if [[ "$USE_SUDO_DOCKER" == "1" ]]; then
  if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
    DOCKER_CMD=(docker)
  elif command -v sudo >/dev/null 2>&1; then
    DOCKER_CMD=(sudo docker)
  else
    echo "Docker unavailable" >&2
    exit 1
  fi
else
  DOCKER_CMD=("$DOCKER_BIN")
fi

if ! command -v gpg >/dev/null 2>&1; then
  echo "gpg is required on the host" >&2
  exit 1
fi
if ! command -v curl >/dev/null 2>&1; then
  echo "curl is required on the host" >&2
  exit 1
fi

TMP_BASE="$(mktemp -d)"
chmod 0700 "$TMP_BASE"
cleanup() {
  rm -rf "$TMP_BASE"
}
trap cleanup EXIT

PHAR_URL="https://phar.phpunit.de/phpunit-4.8.36.phar"
ASC_URL="https://phar.phpunit.de/phpunit-4.8.36.phar.asc"
PHAR_PATH="$TMP_BASE/phpunit-4.8.36.phar"
ASC_PATH="$TMP_BASE/phpunit-4.8.36.phar.asc"
curl -fsSL -o "$PHAR_PATH" "$PHAR_URL"
curl -fsSL -o "$ASC_PATH" "$ASC_URL"

EXPECTED_SHA="ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e"
ACTUAL_SHA="$(sha256sum "$PHAR_PATH" | awk '{print $1}')"
if [[ "$ACTUAL_SHA" != "$EXPECTED_SHA" ]]; then
  echo "PHPUnit PHAR SHA-256 mismatch" >&2
  exit 1
fi

GNUPGHOME="$(mktemp -d "$TMP_BASE/gnupg.XXXXXX")"
chmod 0700 "$GNUPGHOME"
export GNUPGHOME
gpg --batch --keyserver hkps://keyserver.ubuntu.com --recv-keys D8406D0D82947747293778314AA394086372C20A >/dev/null 2>&1
VERIFY_OUT="$(gpg --batch --status-fd 1 --verify "$ASC_PATH" "$PHAR_PATH" 2>&1)" || true
if ! echo "$VERIFY_OUT" | grep -q '\[GNUPG:\] VALIDSIG'; then
  echo "PHPUnit PHAR signature verification failed" >&2
  exit 1
fi
FINGERPRINT="$(echo "$VERIFY_OUT" | grep '\[GNUPG:\] VALIDSIG' | awk '{print $NF}')"
if [[ "$FINGERPRINT" != "D8406D0D82947747293778314AA394086372C20A" ]]; then
  echo "PHPUnit PHAR fingerprint mismatch" >&2
  exit 1
fi

wait_db() {
  local attempts=30
  local i=0
  while [[ $i -lt $attempts ]]; do
    if command -v mariadb-admin >/dev/null 2>&1; then
      if mariadb-admin --protocol=TCP -h 127.0.0.1 -u statsreg -pstatsreg ping >/dev/null 2>&1; then
        return 0
      fi
    fi
    i=$((i + 1))
    sleep 1
  done
  echo "MariaDB not ready" >&2
  return 1
}

if ! wait_db; then
  if [[ -x "$ROOT/tests/setup-mariadb.sh" ]]; then
  "$ROOT/tests/setup-mariadb.sh" || true
  fi
  wait_db
fi

IMAGE_TAG="statsreg-php56"
"${DOCKER_CMD[@]}" build -t "$IMAGE_TAG" -f "$TESTS_DIR/Dockerfile.php56" "$TESTS_DIR"

"${DOCKER_CMD[@]}" run --rm --network host \
  -v "$ROOT:/work:ro" \
  -w /work \
  -e STATSREG_DB_HOST=127.0.0.1 \
  -e STATSREG_DB_PORT=3306 \
  -e STATSREG_DB_NAME=statsreg_test \
  -e STATSREG_DB_USER=statsreg \
  -e STATSREG_DB_PASSWORD=statsreg \
  -e PHPUNIT_PHAR=/tmp/phpunit.phar \
  -v "$PHAR_PATH:/tmp/phpunit.phar:ro" \
  "$IMAGE_TAG" \
  php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 \
    /work/tests/run-suite.php
