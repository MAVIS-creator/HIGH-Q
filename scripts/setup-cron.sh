#!/bin/bash

################################################################################
# Linux/Mac Cron Setup for Security Scan Scheduler
################################################################################
#
# This script sets up a cron job to run the security scan scheduler 
# automatically every day at 2:00 AM.
#
# Requirements:
# - Linux or macOS
# - Bash shell
# - Access to crontab
# - PHP installed
#
# Usage: chmod +x setup-cron.sh && ./setup-cron.sh
#

echo ""
echo "============================================================"
echo "Linux/Mac Cron Setup - Security Scan Scheduler"
echo "============================================================"
echo ""

# Detect OS
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    OS="Linux"
elif [[ "$OSTYPE" == "darwin"* ]]; then
    OS="macOS"
else
    echo "ERROR: Unsupported OS: $OSTYPE"
    exit 1
fi

echo "[1/5] Detecting system information..."
echo "      OS: $OS"
echo "      User: $(whoami)"
echo ""

# Check if PHP is installed
echo "[2/5] Checking PHP installation..."
PHP_PATH=$(which php)

if [ -z "$PHP_PATH" ]; then
    echo "ERROR: PHP not found!"
    echo ""
    echo "Please install PHP or add it to your PATH."
    exit 1
fi

echo "      Found PHP at: $PHP_PATH"
echo "      Version: $(php -v | head -n 1)"
echo ""

# Determine project path (this script should be at /path/to/HIGH-Q/setup-cron.sh)
echo "[3/5] Detecting project path..."
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
SCHEDULER_PATH="$SCRIPT_DIR/bin/scan-scheduler.php"

if [ ! -f "$SCHEDULER_PATH" ]; then
    echo "ERROR: scan-scheduler.php not found at $SCHEDULER_PATH"
    exit 1
fi

echo "      Project: $SCRIPT_DIR"
echo "      Scheduler: $SCHEDULER_PATH"
echo ""

# Build cron command
CRON_COMMAND="0 2 * * * $PHP_PATH $SCHEDULER_PATH >> /dev/null 2>&1"

echo "[4/5] Setting up cron job..."
echo "      Time: 02:00 (2:00 AM) daily"
echo "      Command: $CRON_COMMAND"
echo ""

# Check if cron entry already exists
CURRENT_CRON=$(crontab -l 2>/dev/null | grep "scan-scheduler.php" || true)

if [ ! -z "$CURRENT_CRON" ]; then
    echo "      Found existing cron entry:"
    echo "      $CURRENT_CRON"
    echo ""
    read -p "      Replace existing entry? (y/n) " -n 1 -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "      Skipped - keeping existing entry"
        echo ""
    else
        # Remove old entry and add new one
        (crontab -l 2>/dev/null | grep -v "scan-scheduler.php"; echo "$CRON_COMMAND") | crontab -
        echo "      ✓ Updated existing cron entry"
        echo ""
    fi
else
    # Add new cron entry
    (crontab -l 2>/dev/null; echo "$CRON_COMMAND") | crontab -
    echo "      ✓ Cron job created successfully!"
    echo ""
fi

echo "[5/5] Verifying cron setup..."
VERIFY_CRON=$(crontab -l 2>/dev/null | grep "scan-scheduler.php" || true)

if [ ! -z "$VERIFY_CRON" ]; then
    echo "      ✓ Cron job verified!"
    echo ""
else
    echo "      ERROR: Cron job verification failed!"
    exit 1
fi

echo "============================================================"
echo "Setup Complete!"
echo "============================================================"
echo ""
echo "The security scan scheduler is now configured to run:"
echo "  • Every day at 2:00 AM"
echo "  • Automatically scans your system"
echo "  • Sends email reports on critical findings"
echo ""
echo "Cron Entry:"
echo "  $CRON_COMMAND"
echo ""
echo "Management:"
echo "  View all cron jobs:  crontab -l"
echo "  Edit cron jobs:      crontab -e"
echo "  Remove cron job:     crontab -r"
echo ""
echo "Logs:"
echo "  Check: $SCRIPT_DIR/storage/logs/php-error.log"
echo ""
echo "Next Steps:"
echo "  1. Verify entry appears in: crontab -l"
echo "  2. Monitor logs directory for confirmations"
echo "  3. Configure scan settings in Admin panel if needed"
echo ""
