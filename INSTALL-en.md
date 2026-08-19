# Lime Blog · Installation Guide

[Chinese](./INSTALL.md)

A lightweight personal blogging system built entirely with PHP and MySQL, no Composer required—just upload and start using.


---


## 1. Environmental Requirements

| Component | Requirement |
| --- | --- |
| PHP | Version ≥ 7.4 (recommended: 8.0+) |
| Database | MySQL 5.7+ or MariaDB 10.3+ |
| PHP Extensions | `pdo`, `pdo_mysql`, `mbstring`, `json`, `finfo` (required); `gd` (optional, for CAPTCHA/images) |
| Directory Permissions | The `uploads/` directory must be writable (for image uploads) |

> The CAPTCHA feature depends on the `gd` extension; without it, human verification for comments will not display. It is recommended to enable it.


---


## 2. Deployment Steps

### Method A: Virtual Host / Nginx / Apache (Production Deployment)

1. **Upload Source Code**: Upload all files from the `blog/` directory to your site's root directory (or a subdirectory, such as `htdocs/blog`).
   - The site root should point inside the `blog/` directory, so the homepage corresponds to `blog/index.php`.
2. **Verify Directory Permissions**: Ensure the `uploads/` directory is writable:
```bash
chmod -R 755 uploads
Some virtual hosts require 775 or 777 permissions, depending on the server configuration.
```
3. **Pseudo-static (optional)**: All URLs in this system are in the format `xxx.php` and can run normally without pseudo-static configuration by default.  
- If using HTTPS with Nginx, please ensure PHP-FPM is properly forwarded.

### Method B: Local PHP Built-in Server (quick testing)


```bash
cd blog
php -S 127.0.0.1:8080
```


Access <http://127.0.0.1:8080> in your browser to enter the installation wizard.


---


## 3. Run the Installation Wizard

Open your browser and go to `http://your-domain/install/index.php`, then complete the following four steps:

### Step 1: Environment Check  
Check each version and extension item by item. Click "Next" once all items are checked (or only if `gd` is not enabled).

### Step 2: Database Configuration  
Enter the MySQL connection information:

| Field | Description |
| --- | --- |
| Database Host | Default `127.0.0.1`; for cloud databases, enter the corresponding address |
| Port | Default `3306` |
| Database Name | Only letters, digits, and underscores allowed (e.g., `blog`; will be automatically created if it doesn't exist) |
| Database User / Password | An account with database creation privileges |

> The wizard will automatically create the database and all required tables (`settings`, `posts`, `friends`, `comments`); no manual table creation is needed.

### Step 3: Site and Admin
- **Site Name**: Required.
- **Tagline / Subtitle**: Displayed in the hero section on the homepage; can be changed later.
- **Admin Username**: 2–32 characters, limited to letters, digits, underscores, and hyphens.
- **Admin Password**: At least 8 characters, must be entered twice.
- **Installation Directory**:
  - Root installation (domain points directly to site): **Leave blank**;
  - Subdirectory installation (e.g., `http://yourdomain.com/blog`): Enter `/blog`.

### Step 4: Complete
Once you see "Installation Successful," you can access the homepage. The admin panel is available at `http://yourdomain.com/admin/`.


---


## 4. Post-Installation Recommendations

1. **Delete the installation directory** (safety first):
```bash
rm -rf install
```
After deletion, the installation wizard can no longer be accessed, preventing others from reinstalling or tampering with the configuration.  
2. **Keep `config.php` secure**: It contains your database password; do not expose it publicly or commit it to a code repository.  
3. Upon first use, we recommend logging into the admin panel and updating your personal information—such as your avatar, name, bio, and announcements—in the "Homepage Settings" section.


---


## 5. Quick Overview of Admin Features

Admin URL: `/admin/`

| Menu | Functionality |
| --- | --- |
| Console | Site Data Overview |
| Article Management | Publish/edit/delete articles (Markdown writing) |
| Comment Management | Approve/delete comments (new comments are pending by default) |
| Homepage Settings | Hero section, About Me (avatar/name/bio), number of latest articles, etc. |
| Site Settings | Site information, ICP registration, custom CSS, etc. |
| Friend Links | Manage friend links |


---


## 6. Frequently Asked Questions

**Q: Why does the installation prompt "Database connection/initialization failed"?** **
Check whether the database host, port, username, and password are correct, and ensure that the account has permission to create databases.

**Q: Comment avatars not displaying? **
Using a QQ email for comments will automatically request the QQ avatar interface; non-QQ emails will display a default placeholder avatar. Please ensure the page can access the internet.

**Q: Why isn't the CAPTCHA image showing? **
The new version of the CAPTCHA has been downgraded: it outputs PNG when GD is available, and automatically
