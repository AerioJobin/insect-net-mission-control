# Manual Deployment — Step-by-Step Commands

## Prerequisites: SSH Setup

If you haven't set up SSH keys yet, do this first:

### 1a. Generate SSH Key
```powershell
ssh-keygen -t rsa -b 4096 -f "$env:USERPROFILE\.ssh\id_rsa" -N ""
```

### 1b. Add SSH Key to EC2 (One-time Setup)

You'll need your EC2 PEM file. If you have `insect-net-key.pem`:

```powershell
# Convert PEM to OpenSSH format (if needed)
ssh-keygen -i -f "C:\path\to\insect-net-key.pem" | Out-File "$env:USERPROFILE\.ssh\authorized_keys"

# Or directly add your public key if you have SSH key-pair already configured
cat "$env:USERPROFILE\.ssh\id_rsa.pub"
# Then manually paste this on EC2 into: ~/.ssh/authorized_keys
```

---

## Deployment Steps

### Step 0: Prepare Local Environment
```powershell
# Navigate to project directory
cd "c:\Users\aerio\OneDrive\Desktop\Project\IISc\webdash"

# Verify all files are present
$files = @("config.php", "login.php", "logout.php", "index.php", "delete_image.php")
foreach ($f in $files) {
    if (Test-Path $f) { Write-Host "✓ $f" -ForegroundColor Green }
    else { Write-Host "✗ $f MISSING!" -ForegroundColor Red }
}
```

### Step 1: Backup Existing Files on Server
```bash
# SSH to your EC2 server
ssh -i "C:\path\to\insect-net-key.pem" ec2-user@65.2.30.116

# On the server, create timestamped backups:
cd /var/www/html

# If these files exist, back them up
cp index.php index.php.backup.2026-03-30 2>/dev/null
cp delete_image.php delete_image.php.backup.2026-03-30 2>/dev/null
cp config.php config.php.backup.2026-03-30 2>/dev/null
cp login.php login.php.backup.2026-03-30 2>/dev/null
cp logout.php logout.php.backup.2026-03-30 2>/dev/null

ls -la *.backup*
# Exit SSH
exit
```

### Step 2: Upload New Files via SCP

From PowerShell on your local machine:

```powershell
$KeyPath = "C:\path\to\insect-net-key.pem"
$Server = "65.2.30.116"
$RemotePath = "/var/www/html/"
$LocalPath = "c:\Users\aerio\OneDrive\Desktop\Project\IISc\webdash\"

# Upload each file
$files = @("config.php", "login.php", "logout.php", "index.php", "delete_image.php")

foreach ($file in $files) {
    scp -i $KeyPath "$LocalPath\$file" "ec2-user@$($Server):$RemotePath"
    Write-Host "✓ Uploaded $file"
}
```

Or using individual SCP commands:

```bash
scp -i "C:\path\to\insect-net-key.pem" "c:\Users\aerio\OneDrive\Desktop\Project\IISc\webdash\config.php" "ec2-user@65.2.30.116:/var/www/html/"
scp -i "C:\path\to\insect-net-key.pem" "c:\Users\aerio\OneDrive\Desktop\Project\IISc\webdash\login.php" "ec2-user@65.2.30.116:/var/www/html/"
scp -i "C:\path\to\insect-net-key.pem" "c:\Users\aerio\OneDrive\Desktop\Project\IISc\webdash\logout.php" "ec2-user@65.2.30.116:/var/www/html/"
scp -i "C:\path\to\insect-net-key.pem" "c:\Users\aerio\OneDrive\Desktop\Project\IISc\webdash\index.php" "ec2-user@65.2.30.116:/var/www/html/"
scp -i "C:\path\to\insect-net-key.pem" "c:\Users\aerio\OneDrive\Desktop\Project\IISc\webdash\delete_image.php" "ec2-user@65.2.30.116:/var/www/html/"
```

### Step 3: Set File Permissions

SSH into server and set permissions:

```bash
ssh -i "C:\path\to\insect-net-key.pem" ec2-user@65.2.30.116

cd /var/www/html

# Set correct permissions
chmod 644 config.php login.php logout.php index.php delete_image.php

# Verify
ls -la config.php login.php logout.php index.php delete_image.php

# Should show: -rw-r--r--

exit
```

### Step 4: Verify PHP Syntax

```bash
ssh -i "C:\path\to\insect-net-key.pem" ec2-user@65.2.30.116

cd /var/www/html

php -l config.php
php -l login.php
php -l logout.php
php -l index.php
php -l delete_image.php

# All should return: "No syntax errors detected in ..."

exit
```

### Step 5: Run Deployment Verification Script

```bash
ssh -i "C:\path\to\insect-net-key.pem" ec2-user@65.2.30.116

cd /var/www/html

# Copy verify.php to server first:
# scp -i "insect-net-key.pem" verify.php ec2-user@65.2.30.116:/var/www/html/

php verify.php

# Should show: "ALL CHECKS PASSED"

exit
```

---

## Testing After Deployment

### 1. Clear Browser Cache
- Open DevTools (F12)
- Storage → Cookies → Delete all
- Or use incognito/private window

### 2. Test Landing Page
```
http://65.2.30.116/index.php?view=landing
```
- Should show brand logos + "ENTER DASHBOARD" button

### 3. Test Admin Login
```
URL: http://65.2.30.116/login.php
Username: admin
Password: iisc_admin_2026
```
- Should redirect to dashboard
- Header should show: "admin" + "ADMIN"
- Delete buttons should be visible on images

### 4. Test Researcher Login
```
URL: http://65.2.30.116/login.php
Username: researcher
Password: insect_user_2026
```
- Should redirect to dashboard
- Header should show: "researcher" + "USER"
- Delete buttons should NOT appear

### 5. Test Logout
- Click "Logout" button
- Should see success message
- Clicking back should NOT work

### 6. Test Session Timeout (optional)
- Log in
- Wait 30+ minutes without activity
- Refresh page
- Should redirect to login with "Session expired" message

---

## Troubleshooting

### SSH Connection Refused
```powershell
# Test SSH connectivity
ssh -i "path\to\key.pem" -v ec2-user@65.2.30.116

# Check if EC2 security group allows port 22
# On AWS Console: EC2 → Security Groups → Inbound Rules (should allow SSH from your IP)
```

### Permission Denied (publickey)
```bash
# Verify authorized_keys on server
ssh ec2-user@65.2.30.116
cat ~/.ssh/authorized_keys
ls -la ~/.ssh/
# Should show: -rw------- authorized_keys

# Check key permissions locally
ls -la "$env:USERPROFILE\.ssh\id_rsa"
# Should show: -rw------- (600 permission)
```

### SCP Upload Fails
```powershell
# Test SCP connectivity
scp -i "path\to\key.pem" -v "localfile" "ec2-user@65.2.30.116:/tmp/"

# Verify remote directory is writable
ssh ec2-user@65.2.30.116 "ls -la /var/www/html && touch /var/www/html/.test && rm /var/www/html/.test"
```

### PHP Syntax Errors
```bash
ssh ec2-user@65.2.30.116

# Check exact error
php -l /var/www/html/config.php -v

# View error log
sudo tail -20 /var/log/apache2/error.log
```

---

## Rollback Plan

If something goes wrong:

```bash
ssh -i "path\to\key.pem" ec2-user@65.2.30.116

cd /var/www/html

# Restore from backups
cp index.php.backup.2026-03-30 index.php
cp delete_image.php.backup.2026-03-30 delete_image.php

# Remove new files
rm config.php login.php logout.php

# Verify
ls -la index.php delete_image.php

exit
```

---

## Getting Help

If stuck at any point:

1. Check error logs:
   ```bash
   ssh ec2-user@65.2.30.116
   sudo tail -50 /var/log/apache2/error.log
   ```

2. Verify files on server:
   ```bash
   ssh ec2-user@65.2.30.116
   ls -la /var/www/html/config.php /var/www/html/login.php
   ```

3. Test PHP directly:
   ```bash
   ssh ec2-user@65.2.30.116
   php -r "include '/var/www/html/config.php'; echo 'config loaded OK';"
   ```
