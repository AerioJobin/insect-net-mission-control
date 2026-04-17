#!/bin/bash
# Insect NET — EC2 Deployment Script
# Usage: ./deploy.sh 65.2.30.116 ec2-user

SERVER="${1:-65.2.30.116}"
USER="${2:-ec2-user}"
REMOTE_PATH="/var/www/html/"
LOCAL_PATH="$(pwd)"

timestamp=$(date +%Y-%m-%d_%H%M%S)

echo ""
echo "╔════════════════════════════════════════╗"
echo "║  Insect NET — EC2 Deployment Script     ║"
echo "╚════════════════════════════════════════╝"
echo ""
echo "✓ Target: $SERVER ($USER@$SERVER:$REMOTE_PATH)"
echo "✓ Source: $LOCAL_PATH"
echo "✓ Timestamp: $timestamp"
echo ""

# Check if SSH key exists
if ! command -v ssh &> /dev/null; then
    echo "✗ SSH not found. Please install OpenSSH."
    exit 1
fi

# Verify local files
echo "Checking local files..."
for file in config.php login.php logout.php index.php delete_image.php; do
    if [ -f "$file" ]; then
        echo "  ✓ $file"
    else
        echo "  ✗ $file (MISSING!)"
        exit 1
    fi
done

echo ""
read -p "Continue deployment? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Deployment cancelled."
    exit 0
fi

echo ""
echo "Step 1: Creating backup on server..."
ssh "$USER@$SERVER" "cd $REMOTE_PATH && cp index.php index.php.backup.$timestamp 2>/dev/null && cp delete_image.php delete_image.php.backup.$timestamp 2>/dev/null && echo '✓ Backups created'"

echo ""
echo "Step 2: Uploading files..."
for file in config.php login.php logout.php index.php delete_image.php; do
    scp -q "$file" "$USER@$SERVER:$REMOTE_PATH$file"
    echo "  ✓ $file uploaded"
done

echo ""
echo "Step 3: Setting permissions..."
ssh "$USER@$SERVER" "cd $REMOTE_PATH && chmod 644 config.php login.php logout.php index.php delete_image.php && echo '✓ Permissions set'"

echo ""
echo "Step 4: PHP Syntax Check..."
ssh "$USER@$SERVER" "cd $REMOTE_PATH && php -l config.php && php -l login.php && php -l logout.php && php -l index.php && php -l delete_image.php" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "  ✓ All PHP files validated"
else
    echo "  ✗ PHP syntax error detected"
    exit 1
fi

echo ""
echo "╔════════════════════════════════════════╗"
echo "║  Deployment Completed Successfully     ║"
echo "╚════════════════════════════════════════╝"
echo ""
echo "Next steps:"
echo "  1. Clear browser cache/cookies"
echo "  2. Visit: http://$SERVER/index.php?view=landing"
echo "  3. Test login:"
echo "     • admin / iisc_admin_2026"
echo "     • researcher / insect_user_2026"
echo ""
echo "Backup timestamp: $timestamp"
echo ""
