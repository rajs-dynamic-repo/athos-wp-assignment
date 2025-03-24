# athos-wp-assignment

## 🚀 How to Run This WordPress Project on Local

1️⃣ Clone the Repository

2️⃣ Start Docker Containers

3️⃣ Import Database

4️⃣ Update wp_options Table (If Needed)
If the site doesn't load properly, update site URL via MySQL:: ` UPDATE wp_options SET option_value = 'http://localhost:8000' WHERE option_name IN ('siteurl', 'home');`

5️⃣ Login to WordPress
URL: http://localhost:8000/wp-admin

User: athos_raj

Pass: athos_raj_athos_23

## 🐳 WordPress Setup in Docker 
> Why Docker?
Ensures a consistent, portable, and isolated development environment without affecting the local system.

> Setup Process:
Created a docker-compose.yml file to set up WordPress, MySQL, and phpMyAdmin.

> Mapped local volumes for persistent data storage.

## 🛠️ GitHub Integration for Version Control
> To track changes and ensure rollback safety.

> Added .gitignore to exclude unnecessary files 

## 🚀 WordPress SEO Best Practices Implementation
This assignment follows industry best practices for both technical and non-technical SEO to ensure high performance, structured data, and search engine visibility.

1️⃣ Schema Markup (JSON-LD) 
2️⃣ XML Sitemap for Indexing
3️⃣ robots.txt Optimization 
4️⃣ Page Speed & Caching
5️⃣ Mobile-Friendly & Accessibility 

## 🚀 Multi-lingual Plugin: Polylang
### ✔ How It’s Implemented?
- >Registered languages programmatically using pll_register_string().
- >Ensured translation compatibility with ACF fields and custom post types.

- > 📌 Why Not Fully Implemented?
Due to limited time, the full multilingual setup (menu translations, string translation) was not completed. Some aspects require manual adjustments in templates, making it more time-consuming in this phase.

## 🚀 WordPress Security Enhancements 
To ensure my site is secure and protected from brute force, injections, and unauthorized access, 
I’ve implemented the following security measures.

1️⃣ Secure WordPress Installation
 Removed unused themes & plugins to reduce vulnerabilities.
 Installed Wordfence for added protection.
 Disabled directory browsing to prevent file snooping.

2️⃣ Secure Contact Forms
 Disabled XML-RPC to block external unauthorized requests.

3️⃣ Hide WordPress Version

4️⃣ Secure wp-config.php

 
