# XAMPP Setup Guide - Warehouse Management System
# اردو میں ہدایات / Urdu Instructions

## آپشن 1: Virtual Host Setup (Recommended - warehouse.local)

### Step 1: Apache Virtual Host Configuration
1. **File kholen**: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
2. **Is file ke end mein ye code add karen**:

```apache
<VirtualHost *:80>
    ServerName warehouse.local
    ServerAlias www.warehouse.local
    DocumentRoot "C:/xampp/htdocs/Warehouse-Management-System/public"
    
    <Directory "C:/xampp/htdocs/Warehouse-Management-System/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/warehouse-error.log"
    CustomLog "logs/warehouse-access.log" common
</VirtualHost>
```

### Step 2: Hosts File Edit
1. **Notepad ko Administrator mode mein kholen**
2. **File kholen**: `C:\Windows\System32\drivers\etc\hosts`
3. **Is line ko add karen**:
```
127.0.0.1    warehouse.local
```

### Step 3: Apache Restart
1. XAMPP Control Panel kholen
2. Apache ko **Stop** karen
3. Apache ko **Start** karen

### Step 4: Browser mein access karen
```
http://warehouse.local
```

---

## آپشن 2: Simple localhost/warehouse Setup (Easier)

### Step 1: httpd.conf Check
1. **File kholen**: `C:\xampp\apache\conf\httpd.conf`
2. **Ye line dhundhen aur ensure karen ke uncommented hai**:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

### Step 2: .htaccess Enable
**Same file mein dhundhen**:
```apache
<Directory "C:/xampp/htdocs">
    AllowOverride None
```

**Change karen to**:
```apache
<Directory "C:/xampp/htdocs">
    AllowOverride All
```

### Step 3: Apache Restart
1. XAMPP Control Panel kholen
2. Apache ko **Stop** karen
3. Apache ko **Start** karen

### Step 4: Browser mein access karen
```
http://localhost/Warehouse-Management-System/public
```

---

## آپشن 3: Symlink Setup (Best for Development)

### Windows Command Prompt (Administrator mode mein):
```cmd
cd C:\xampp\htdocs
mklink /D warehouse "C:\xampp\htdocs\Warehouse-Management-System\public"
```

### Access URL:
```
http://localhost/warehouse
```

---

## .env Configuration

Apni `.env` file mein ye settings check karen:

```env
APP_URL=http://warehouse.local
# Ya agar localhost use kar rahe hain:
# APP_URL=http://localhost/Warehouse-Management-System/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=warehouse_db
DB_USERNAME=root
DB_PASSWORD=
```

---

## Troubleshooting

### Problem: 404 Not Found
**Solution**: 
- Check ke `.htaccess` file `public` folder mein hai
- Apache `mod_rewrite` enabled hai
- `AllowOverride All` set hai

### Problem: Permission Denied
**Solution**:
```cmd
cd C:\xampp\htdocs\Warehouse-Management-System
icacls storage /grant Everyone:(OI)(CI)F /T
icacls bootstrap\cache /grant Everyone:(OI)(CI)F /T
```

### Problem: Database Connection Error
**Solution**:
- XAMPP Control Panel mein MySQL start karen
- phpMyAdmin mein database create karen: `warehouse_db`
- `.env` file mein credentials check karen

---

## Quick Commands

### Clear Cache:
```cmd
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Generate Application Key (agar nahi hai):
```cmd
php artisan key:generate
```

### Run Migrations:
```cmd
php artisan migrate
```

---

## Recommended URL Structure

**Best Practice**: Use Virtual Host
```
http://warehouse.local
```

**Advantages**:
- Clean URLs
- No `/public` in URL
- Professional setup
- Easy to remember

---

## Current Status

✅ `.htaccess` file created in `public` folder
✅ Virtual Host configuration file created: `XAMPP_SETUP.conf`
✅ Project location: `C:\xampp\htdocs\Warehouse-Management-System`

**Next Steps**:
1. Choose koi ek option (1, 2, ya 3)
2. Follow the steps
3. Apache restart karen
4. Browser mein test karen

---

## Support

Agar koi issue aaye to:
1. Apache error log check karen: `C:\xampp\apache\logs\error.log`
2. Laravel log check karen: `storage\logs\laravel.log`
3. Browser console check karen (F12)
