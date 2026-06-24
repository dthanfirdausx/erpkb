#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

PHP_BIN="${PHP_BIN:-php}"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUT_DIR="$ROOT_DIR/outputs/deployment"
PACKAGE="$OUT_DIR/erpkb_deploy_$STAMP.tar.gz"
MANIFEST="$OUT_DIR/erpkb_deploy_$STAMP.manifest.txt"

mkdir -p "$OUT_DIR"

{
  echo "ERPKB Deployment Package"
  echo "Generated at: $(date '+%Y-%m-%d %H:%M:%S')"
  echo "Root: $ROOT_DIR"
  echo
  echo "== P0 Hardening Audit =="
  "$PHP_BIN" database/scripts/run_p0_hardening_audit.php
  echo
  echo "== Active Legacy Report Reference Scan =="
  "$PHP_BIN" database/scripts/scan_active_legacy_report_refs.php
  echo
  echo "== Active Module UI Audit =="
  "$PHP_BIN" database/scripts/audit_active_module_ui.php
  echo
  echo "== Package Excludes =="
  cat <<'EOF'
.git/
.codex_tmp/
outputs/
database/backups/
.DS_Store
modul copy/
*.log
*.tmp
EOF
} | tee "$MANIFEST"

tar -czf "$PACKAGE" \
  --exclude="./.git" \
  --exclude="./.codex_tmp" \
  --exclude="./outputs" \
  --exclude="./database/backups" \
  --exclude="./.DS_Store" \
  --exclude="./modul copy" \
  --exclude="*.log" \
  --exclude="*.tmp" \
  .

{
  echo
  echo "== Package Result =="
  echo "Package: $PACKAGE"
  echo "Manifest: $MANIFEST"
  echo "Size bytes: $(wc -c < "$PACKAGE" | tr -d ' ')"
} | tee -a "$MANIFEST"

echo "Deployment package created: $PACKAGE"
