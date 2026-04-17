# Insect NET Dashboard — EC2 Deployment Guide

**Server:** `65.2.30.116`  
**Remote Path:** `/var/www/html/`  
**Date:** March 30, 2026

---

## Files to Deploy (4 total)

| File | Type | Status | Action |
|------|------|--------|--------|
| `config.php` | NEW | Security | Create / Upload |
| `login.php` | NEW | Authentication | Create / Upload |
| `logout.php` | NEW | Auth | Create / Upload |
| `index.php` | MODIFIED | Dashboard | Replace |
| `delete_image.php` | MODIFIED | API | Replace |

---

## Step 1: Backup Originals on Server

Connect to EC2 and backup existing files:

```bash
cd /var/www/html

# Backup index.php and delete_image.php
cp index.php index.php.backup.2026-03-30
cp delete_image.php delete_image.php.backup.2026-03-30

# List to verify
ls -lah *.backup*
```

---

## Step 2: Upload Files via SCP (Recommended)

From your **local machine** (Windows PowerShell):

### 2a. New Files (config.php, login.php, logout.php)

```powershell
# Set variables
$EC2_USER = "ec2-user"  # or "ubuntu" depending on AMI
$EC2_HOST = "65.2.30.116"
$EC2_PATH = "/var/www/html/"
$LOCAL_PATH = "c:\Users\aerio\OneDrive\Desktop\Project\IISc\webdash\"

# Upload new security files
scp -r "$LOCAL_PATH\config.php" "${EC2_USER}@${EC2_HOST}:${EC2_PATH}"
scp -r "$LOCAL_PATH\login.php" "${EC2_USER}@${EC2_HOST}:${EC2_PATH}"
scp -r "$LOCAL_PATH\logout.php" "${EC2_USER}@${EC2_HOST}:${EC2_PATH}"
```

### 2b. Modified Files (index.php, delete_image.php)

```powershell
# Upload updated files (overwrites old versions)
scp -r "$LOCAL_PATH\index.php" "${EC2_USER}@${EC2_HOST}:${EC2_PATH}"
scp -r "$LOCAL_PATH\delete_image.php" "${EC2_USER}@${EC2_HOST}:${EC2_PATH}"
```

---

## Step 3: Verify File Permissions

Connect to EC2 via SSH and set permissions:

```bash
cd /var/www/html

# Check current permissions
ls -la config.php login.php logout.php index.php delete_image.php

# Set correct permissions (readable by web server)
chmod 644 config.php login.php logout.php index.php delete_image.php

# Verify ownership
sudo chown www-data:www-data config.php login.php logout.php
```

---

## Step 4: PHP Configuration Check

Verify PHP settings on the server:

```bash
# Check PHP version
php --version

# Verify bcrypt support (needed for passwords)
php -r "echo phpversion('openssl');"

# Check session path writable
ls -la /var/lib/php/sessions/
```

---

## Step 5: Test Deployment

### 5a. Quick Health Check

```bash
cd /var/www/html

# Check for syntax errors
php -l config.php
php -l login.php
php -l logout.php
php -l index.php
php -l delete_image.php

# All should return: "No syntax errors detected"
```

### 5b. Test Login Workflow

1. **Clear Browser Cache** (important!)
   - Open DevTools → Storage → Cookies → Delete all
   - Or use incognito/private window

2. **Visit Landing Page**
   ```
   http://65.2.30.116/index.php?view=landing
   ```
   - Should see branding + "ENTER DASHBOARD" button

3. **Try Bad Credentials**
   ```
   Username: admin
   Password: wrong
   ```
   - Should show red error: "Invalid username or password"

4. **Login as Admin**
   ```
   Username: admin
   Password: iisc_admin_2026
   ```
   - Should redirect to Fleet Dashboard
   - User menu should show: "admin" username + "ADMIN" role
   - Delete buttons should be visible on images (if any exist)
   - Logout button should be clickable

5. **Login as Researcher**
   ```
   Username: researcher
   Password: insect_user_2026
   ```
   - Should redirect to Fleet Dashboard
   - User menu should show: "researcher" username + "USER" role
   - Delete buttons should **NOT** appear on images
   - Logout should work

6. **Test Session Timeout** (optional)
   - Wait 30+ minutes inactive
   - Refresh page
   - Should redirect to login.php with "Session expired" message

7. **Test Logout**
   - Click "Logout" button
   - Should redirect to login with "Successfully logged out" message
   - Clicking back shouldn't work (session destroyed)

---

## Step 6: Troubleshooting

### Issue: Blank Page or 500 Error

**Solution:** Check PHP error logs
```bash
sudo tail -50 /var/log/apache2/error.log
# or for Nginx:
sudo tail -50 /var/log/nginx/error.log
```

### Issue: "Access Denied" on `/dashboard`

**Solution:** Verify config.php is included and readable
```bash
# Check if file exists
test -f /var/www/html/config.php && echo "✓ config.php exists" || echo "✗ Missing"

# Check if readable by web server
sudo -u www-data php -r "include '/var/www/html/config.php'; echo 'OK';"
```

### Issue: Login Not Working

**Solution:** Verify passwords are hashing correctly
```bash
php -r "
include '/var/www/html/config.php';
echo 'Admin pwd valid: ' . (verifyPassword('iisc_admin_2026', \$users['admin']['password']) ? 'YES' : 'NO') . PHP_EOL;
echo 'User pwd valid: ' . (verifyPassword('insect_user_2026', \$users['researcher']['password']) ? 'YES' : 'NO') . PHP_EOL;
"
```

### Issue: Images Can't Be Deleted

**Solution:** Check delete_image.php permissions and uploads folder
```bash
ls -la /var/www/html/delete_image.php
ls -la /var/www/html/uploads/
# uploads folder should have write permission: drwxrwxrwx or 777
```

---

## Step 7: Post-Deployment Security

1. **Hide Demo Credentials** (optional, for production)
   - Edit `login.php` and remove or hide the demo hints section
   
2. **Disable Directory Listing**
   ```bash
   # Ensure .htaccess exists with:
   # Options -Indexes
   ```

3. **Set Secure Session Cookies** (edit config.php)
   ```php
   session_set_cookie_params([
       'secure' => true,      // HTTPS only
       'httponly' => true,    // Not accessible via JS
       'samesite' => 'Strict' // CSRF protection
   ]);
   ```

4. **Rotate uploaded files location**
   - Move `uploads/` outside web root if possible
   - Store paths in database instead

---

## Rollback Plan

If something breaks, restore backups:

```bash
cd /var/www/html

# Restore from backup
cp index.php.backup.2026-03-30 index.php
cp delete_image.php.backup.2026-03-30 delete_image.php

# Verify
php -l index.php
php -l delete_image.php
```

---

## Monitoring

After deployment, monitor for issues:

```bash
# Watch web server errors in real-time
sudo tail -f /var/log/apache2/error.log

# Check auth attempts
grep "login" /var/log/apache2/access.log | tail -20

# Monitor resource usage
top -u www-data
```

---

## Support

**Emergency Revert:**
```bash
# If all breaks, rollback everything
cd /var/www/html
rm config.php login.php logout.php
cp index.php.backup.2026-03-30 index.php
cp delete_image.php.backup.2026-03-30 delete_image.php
```

Still need help? Check error logs first:
```bash
sudo tail -100 /var/log/apache2/error.log | grep -i "error\|warning"
```
