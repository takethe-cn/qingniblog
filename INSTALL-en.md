# qingniblog · Installation Guide

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
The new version of the CAPTCHA has been downgraded: it outputs PNG when GD is available, and automatically switches to SVG output if GD is missing or lacks PNG support, ensuring image generation in any environment.  
If the CAPTCHA still fails to display, please check:  
- Whether the browser is blocking the request, and whether the page and CAPTCHA are from the same origin;  
- Whether the `captcha.php` path is correct (you can access this URL directly to verify if an image is generated);  
- Whether PHP on the server has `display_errors` enabled and whether error messages appear above—use developer tools to inspect the response content of the CAPTCHA request for confirmation.

**Q: Image upload failed? **
The `uploads/` directory is not writable; please adjust the directory permissions to 755/775/777 and try again.

**Q: Want to switch databases or reinstall? **
After deleting the `config.php` file in the site root directory, simply re-access `install/index.php` (article data will be preserved, but settings will be reset).

**Q: Getting a 404 error when clicking "Next / Submit Database" in the installation wizard?** **
The old version of the wizard incorrectly used `install.php` in internal links, whereas the actual file is `install/index.php`, causing relative links to resolve to a non-existent `install.php` and resulting in a 404 error.  
- Please verify that the content of `install/index.php` no longer contains any references to `install.php` (all should be replaced with `index.php`);  
- If the file remains unchanged, simply re-upload the corrected `install/index.php` to overwrite it.


---


## 7. Directory Structure


```
blog/
├── index.php          Home page (full-screen slideshow: Hero → About → Latest Posts → Footer)  
├── about.php          About Me  
├── blog.php           Blog list  
├── post.php           Post details (with comment system + image CAPTCHA)  
├── friends.php        Links  
├── captcha.php        CAPTCHA image interface for comments  
├── admin/             Admin panel  
├── assets/            Styles, scripts, and images  
├── includes/          Common functions, configuration, CSRF, CAPTCHA, etc.  
├── install/           Installation wizard (delete after setup)  
└── uploads/           Uploaded images (must be writable)
```


---


## 8. About Open Source

- Project homepage and feature introduction: [README.md](./README.md)  
- License: [MIT + Attribution Clause](./LICENSE), commercial use and modification allowed, but original author attribution must be retained  
- Author: takethe-cn; developed with assistance from Trae AI (traeAI)

This document is translated by NetEase Youdao Dictionary.
