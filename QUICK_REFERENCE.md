# Insect NET Deployment — Quick Reference

## 🚀 Quick Deployment (3 Steps)

### Step 1: Backup on Server
```bash
ssh ec2-user@65.2.30.116 "cd /var/www/html && cp index.php index.php.backup && cp delete_image.php delete_image.php.backup"
```

### Step 2: Upload Files
```powershell
# From Windows PowerShell in your project directory
$files = "config.php", "login.php", "logout.php", "index.php", "delete_image.php"
foreach ($f in $files) {
    scp $f ec2-user@65.2.30.116:/var/www/html/
}
```

### Step 3: Run Verification
```bash
ssh ec2-user@65.2.30.116 "cd /var/www/html && php verify.php"
```

---

## 📋 Demo Credentials

| Role | Username | Password |
|------|----------|----------|
| **Admin** | `admin` | `iisc_admin_2026` |
| **User** | `researcher` | `insect_user_2026` |

---

## 🔐 Security Features

✓ Bcrypt password hashing (cost=10)  
✓ 30-minute session timeout  
✓ CSRF token protection  
✓ Role-based access control (admin/user)  
✓ Backend validation on delete operations  
✓ Admin-only delete button visibility  

---

## 🌐 URLs

| Path | Purpose | Auth Required |
|------|---------|---|
| `/index.php?view=landing` | Landing page | NO |
| `/login.php` | Login form | NO |
| `/index.php?view=dashboard` | Fleet/Device dashboard | YES |
| `/logout.php` | Session destruction | YES |
| `/delete_image.php` | Image deletion API | YES (Admin Only) |

---

## 📁 Files Deployed

```
/var/www/html/
├── config.php          ← NEW: Security functions & users
├── login.php           ← NEW: Authentication page
├── logout.php          ← NEW: Session destruction
├── index.php           ← MODIFIED: Added auth checks
├── delete_image.php    ← MODIFIED: Added admin guard
└── uploads/            ← Image storage (auto-created)
```

---

## 🔍 Testing Checklist

- [ ] Visit landing page (no login required)
- [ ] Login with admin account
  - [ ] User menu shows "admin" + "ADMIN"
  - [ ] Delete buttons visible
- [ ] Logout and verify redirect
- [ ] Login with researcher account
  - [ ] User menu shows "researcher" + "USER"
  - [ ] Delete buttons NOT visible
- [ ] Attempt to DELETE image manually in browser console
  - [ ] Admin: Should succeed
  - [ ] Researcher: Should get 403 Forbidden
- [ ] Wait 30+ minutes inactive → should redirect to login
- [ ] CSRF token visible in login form source

---

## 🛠️ Common Commands

### Check Deployment Status
```bash
ssh ec2-user@65.2.30.116 "ls -la /var/www/html/{config,login,logout}.php"
```

### View Recent Errors
```bash
ssh ec2-user@65.2.30.116 "sudo tail -20 /var/log/apache2/error.log"
```

### Rollback
```bash
ssh ec2-user@65.2.30.116 "cd /var/www/html && \
  cp index.php.backup index.php && \
  cp delete_image.php.backup delete_image.php && \
  rm config.php login.php logout.php"
```

### Clear PHP Sessions (if stuck)
```bash
ssh ec2-user@65.2.30.116 "sudo rm /var/lib/php/sessions/sess_*"
```

### Verify PHP Config
```bash
ssh ec2-user@65.2.30.116 "php -r 'phpinfo();' | grep -i 'session\|bcrypt'"
```

---

## 🆘 Troubleshooting

### "Session expired" loop
- Clear browser cookies entirely
- Check `/var/lib/php/sessions/` has write permissions

### Delete button not appearing for admin
- Hard refresh browser (Ctrl+Shift+R)
- Check `$_SESSION['role']` is 'admin' in index.php line 1561

### 403 Forbidden on delete
- Verify delete_image.php has `checkAccess('admin')` at top
- Check permissions: `ls -la /var/www/html/delete_image.php`

### Blank page / 500 error
- SSH to server and run: `php -l config.php`
- Check Apache logs: `sudo tail -50 /var/log/apache2/error.log`

---

## 📊 Server Info

**Host:** 65.2.30.116  
**Path:** /var/www/html/  
**Web Server:** Apache2  
**PHP Version:** 7.4+ (bcrypt required)  
**Session Path:** /var/lib/php/sessions/  

---

## 📝 Notes

- Passwords in config.php are bcrypt hashed  
- CSRF tokens regenerate on every login  
- Session ID should be regenerated (optional enhancement)  
- Uploads should move outside web root in production  
- Consider HTTPS before going live  

---

**Date:** March 30, 2026  
**Version:** 1.0  
