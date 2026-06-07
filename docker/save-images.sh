#!/usr/bin/env bash
# =====================================================================
# Tax-ETS — Build and save Docker image for offline transfer
# ---------------------------------------------------------------------
# Run this on the DEVELOPER machine (with internet) BEFORE going
# to the MOF customer site. It will:
#   1. Build the tax-ets:latest image from ./Dockerfile
#   2. Save it to a single .tar file you can copy to a USB stick
#
# Usage:
#   bash save-images.sh              # build + save
#   bash save-images.sh --skip-build # use existing local image
#
# Output:
#   ./dist/tax-ets-image.tar         # the image to copy to USB
#   ./dist/INSTALL-BUNDLE.tar.gz     # full bundle: image + compose + install
# =====================================================================

set -euo pipefail

SKIP_BUILD=false
if [[ "${1:-}" == "--skip-build" ]]; then
    SKIP_BUILD=true
fi

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
say()  { echo -e "\033[0;34m==>\033[0m $*"; }
ok()   { echo -e "\033[0;32m✓\033[0m $*"; }
err()  { echo -e "\033[0;31m✗\033[0m $*" >&2; }
warn() { echo -e "\033[1;33m!\033[0m $*"; }

DIST_DIR="./dist"
mkdir -p "$DIST_DIR"

# ---- Step 1: Build ----
if $SKIP_BUILD; then
    say "Skipping build (--skip-build given)"
else
    say "Building tax-ets:latest from Dockerfile..."
    docker build -t tax-ets:latest .
    ok "Image built"
fi

# ---- Step 2: Export image to .tar ----
IMAGE_TAR="$DIST_DIR/tax-ets-image.tar"
say "Exporting image to $IMAGE_TAR..."
docker save -o "$IMAGE_TAR" tax-ets:latest
ok "Image saved ($(du -h "$IMAGE_TAR" | cut -f1))"

# ---- Step 3: Package the install bundle ----
BUNDLE_DIR="$DIST_DIR/bundle-contents"
rm -rf "$BUNDLE_DIR"
mkdir -p "$BUNDLE_DIR"

say "Copying image + installer files into bundle..."
cp "$IMAGE_TAR" "$BUNDLE_DIR/"
cp docker/install.sh "$BUNDLE_DIR/"
cp docker-compose.yml "$BUNDLE_DIR/"
cp .env.example "$BUNDLE_DIR/"
cp docker/README.md "$BUNDLE_DIR/README-MOF-INSTALL.md"

BUNDLE_FILE="$DIST_DIR/INSTALL-BUNDLE.tar.gz"
say "Compressing bundle to $BUNDLE_FILE..."
tar -czf "$BUNDLE_FILE" -C "$DIST_DIR" bundle-contents
ok "Bundle ready: $BUNDLE_FILE ($(du -h "$BUNDLE_FILE" | cut -f1))"

# ---- Step 4: Print instructions ----
echo
echo "================================================================"
echo "  MOF DEPLOYMENT BUNDLE READY"
echo "================================================================"
echo
echo "  Image file:     $IMAGE_TAR"
echo "  Bundle file:    $BUNDLE_FILE"
echo
echo "  Next steps:"
echo "    1. Copy $BUNDLE_FILE to a USB stick"
echo "    2. Bring it to the MOF server"
echo "    3. On the MOF server:"
echo "         tar -xzf INSTALL-BUNDLE.tar.gz"
echo "         cd bundle-contents"
echo "         sudo bash install.sh"
echo
warn "Do NOT upload this bundle to the internet — it contains the app image"
warn "and config templates. Keep it on a controlled USB drive."
echo
