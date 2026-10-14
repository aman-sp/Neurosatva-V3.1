#!/bin/bash
set -e

echo "=== Running vercel-php launcher patch ==="

if command -v node >/dev/null 2>&1; then
  node patch-vercel-php.js || true
fi

for f in $(find /vercel /root /tmp . -name "*.js" -path "*vercel-php*" 2>/dev/null || true); do
  if grep -q "launcher.launcher" "$f" 2>/dev/null; then
    sed -i "s/launcher\.launcher/launcher\.js/g" "$f"
    echo "[sed] Patched vercel-php in: $f"
  fi
done

echo "=== vercel-php patch finished ==="
