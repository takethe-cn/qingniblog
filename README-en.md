# QingNing Blog  

[Chinese](./README.md)

A lightweight personal blogging system. Built entirely with **PHP + MySQL**, no Composer or Node.js dependencies—just upload and run, featuring a built-in installation wizard.  
> Design inspiration and visual style reference: ttawa.cn.

---

## ✨ Features

- **Install Guide**: Oops, TT forgot to add the installer check. After uploading the files, please visit `https://your-domain.com/install/` to complete setup manually.
- **Full-Screen Page Turning Homepage**: Hero header → About → Latest Posts → Footer; each section occupies one full screen, scrollable for navigation.
- **About Me Page**: Avatar / Name / Bio—easily editable via the admin panel under "Homepage Settings".
- **Comment System**: Username and email are required; website is optional. QQ emails automatically display QQ avatars. Built-in image CAPTCHA (supports GD/SVG, works even without GD).
- **Markdown Writing**: Publish, edit, and delete posts directly from the admin panel.
- **All-in-One Admin Panel**: Manage articles, comments (approve/delete), homepage settings, site configuration, and friend links.
- **Responsive Layout**: Footer sticks to the bottom when content doesn't fill the screen.
- **Single-File Installation Wizard**: Automatically creates database and tables—ready to use out of the box.

---

## 📸 Screenshots  
> Add screenshots here: Homepage / Article page / Comment section / Admin panel.

---

## 🚀 Quick Start

1. Upload the source code to your web root directory (or subdirectory, e.g., `htdocs/blog`).
2. Ensure the `uploads/` directory is writable: `chmod -R 755 uploads`
3. Open `install/index.php` in your browser and follow the 4-step installation wizard.
4. After installation, **delete the `install/` directory**.

For local quick testing:
```bash
cd blog
php -S 127.0.0.1:8080
# Access http://127.0.0.1:8080
```

Detailed steps available in [INSTALL.md](./INSTALL.md).

---

## 🧰 Environment Requirements

| Component | Requirement |
| --- | --- |
| PHP | ≥ 7.4 (recommended 8.0+) |
| Database | MySQL 5.7+ or MariaDB 10.3+ |
| PHP Extensions | `pdo`, `pdo_mysql`, `mbstring`, `json`, `finfo` (required); `gd` (optional, for CAPTCHA) |

---

## 📁 Directory Structure

```text
blog/
├── index.php           # Homepage (full-screen scrolling)
``` Hero → About → Latest Posts → Footer  
├── about.php About Me  
├── blog.php Blog List  
├── post.php Post Details (with comment system + image CAPTCHA)  
├── friends.php Friends Links  
├── captcha.php CAPTCHA image API for comments  
├── admin/ Admin Panel  
├── assets/ Styles / Scripts / Images  
├── includes/ Common functions, configuration, CSRF, CAPTCHA, etc.  
├── install/ Installation Wizard (delete after setup)  
├── uploads/ Uploaded images (must be writable)  
├── LICENSE Open Source License  
└── README.md This document  

```  
## 🛡️ Security Notice  
- `config.php` contains **database passwords**—never commit this to a public repository (this file is already excluded in `.gitignore`)  
- After installation, please delete the `install/` directory to prevent unauthorized reinstallation or configuration tampering  

## 📄 Open Source License  
This project is licensed under [MIT](./LICENSE) with an additional attribution clause:  
- ✅ Commercial use allowed  
- ✅ Modifications and redistribution permitted  
- ✅ Original author must be credited (see "Additional Attribution Clause" in LICENSE)  

## 👤 Developers  
- Author: [takethe-cn](https://github.com/takethe-cn)  
- Developed with assistance from Trae AI (traeAI)  

## ⭐ Support  
If you find this project helpful, feel free to **Star**, **Fork**, or **Submit an Issue**.

This document is translated by NetEase Youdao Dictionary.
