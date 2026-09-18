# 🚀 Advanced SEO Tools & AI Analytics Platform

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Google PageSpeed API](https://img.shields.io/badge/Google-PageSpeed_v5-4285F4?style=for-the-badge&logo=google&logoColor=white)](https://developers.google.com/speed/docs/insights/v5/get-started)
[![Schema.org](https://img.shields.io/badge/Schema.org-Validator-000000?style=for-the-badge)](https://validator.schema.org/)

**Advanced SEO Tools** is an enterprise-level web application and automated SEO audit platform built in **PHP & MySQL**. It offers comprehensive site crawling, Core Web Vitals performance benchmarks, page structure evaluation, image optimization audits, keyword density analysis, competitive comparisons, and AI-driven recommendations.

---

## 🌟 Key Features & Analysis Modules

| Module | Description |
| :--- | :--- |
| ⚡ **Core Web Vitals & Performance** | Integrates Google PageSpeed Insights v5 API to measure **LCP**, **FID**, **CLS**, **FCP**, **TTFB**, **TTI**, and overall performance scores. |
| 🏷️ **Meta Tags & OpenGraph** | Audits title tags, meta descriptions, canonical URLs, viewport settings, robots directives, OpenGraph tags, and Twitter Cards. |
| 📐 **Page Structure & Headings** | Evaluates H1-H6 hierarchy, text-to-HTML ratio, word count, and Schema.org structured data validity (`validator.schema.org`). |
| 🖼️ **Image Optimization Audit** | Scans all images on a page for missing ALT attributes, oversized dimensions, and lazy-loading implementation. |
| 🔗 **Link Architecture & Health** | Analyzes total, internal, external, `nofollow`, and broken links per page. |
| 🔑 **Keyword Density & Rankings** | Measures keyword frequency, density, search volume estimates, difficulty score, and position tracking. |
| 🛡️ **Technical & Off-Page SEO** | Evaluates crawlability, indexability, SSL certificate validity, mobile-friendliness, domain authority, and toxic backlink indicators. |
| ⚔️ **Competitive Analysis** | Performs side-by-side URL comparison against competitor domains. |
| 🤖 **AI Recommendations Engine** | Generates actionable automated SEO optimization reports powered by configured AI models. |

---

## 🗄️ Complete Database Structure (MySQL DDL)

Import this schema to set up the MySQL database (`ai_seo_tools`):

```sql
CREATE DATABASE IF NOT EXISTS `ai_seo_tools` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ai_seo_tools`;

-- 1. WEBSITES TABLE
CREATE TABLE IF NOT EXISTS `websites` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `domain_name` VARCHAR(255) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `date_added` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `last_checked` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. CORE WEB VITALS TABLE
CREATE TABLE IF NOT EXISTS `core_web_vitals` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `website_id` INT(11) NOT NULL,
  `page_url` VARCHAR(255) NOT NULL,
  `lcp` FLOAT DEFAULT NULL,
  `fid` FLOAT DEFAULT NULL,
  `cls` FLOAT DEFAULT NULL,
  `fcp` FLOAT DEFAULT NULL,
  `ttfb` FLOAT DEFAULT NULL,
  `tti` FLOAT DEFAULT NULL,
  `performance_score` INT(11) DEFAULT NULL,
  `scan_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. META TAGS ANALYSIS TABLE
CREATE TABLE IF NOT EXISTS `meta_tags_analysis` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `website_id` INT(11) NOT NULL,
  `page_url` VARCHAR(255) NOT NULL,
  `title` VARCHAR(255) DEFAULT NULL,
  `title_length` INT(11) DEFAULT NULL,
  `meta_description` TEXT,
  `meta_description_length` INT(11) DEFAULT NULL,
  `meta_keywords` TEXT,
  `has_viewport` TINYINT(1) DEFAULT NULL,
  `has_robots` TINYINT(1) DEFAULT NULL,
  `has_canonical` TINYINT(1) DEFAULT NULL,
  `has_og_tags` TINYINT(1) DEFAULT NULL,
  `has_twitter_cards` TINYINT(1) DEFAULT NULL,
  `scan_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. PAGE STRUCTURE TABLE
CREATE TABLE IF NOT EXISTS `page_structure` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `website_id` INT(11) NOT NULL,
  `page_url` VARCHAR(255) NOT NULL,
  `has_h1` TINYINT(1) DEFAULT NULL,
  `heading_structure_json` LONGTEXT,
  `content_html_ratio` FLOAT DEFAULT NULL,
  `main_content_word_count` INT(11) DEFAULT NULL,
  `has_schema_markup` TINYINT(1) DEFAULT NULL,
  `schema_types_json` TEXT,
  `scan_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. IMAGE ANALYSIS TABLE
CREATE TABLE IF NOT EXISTS `image_analysis` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `website_id` INT(11) NOT NULL,
  `page_url` VARCHAR(255) NOT NULL,
  `total_images` INT(11) DEFAULT NULL,
  `images_with_alt` INT(11) DEFAULT NULL,
  `images_without_alt` INT(11) DEFAULT NULL,
  `oversized_images` INT(11) DEFAULT NULL,
  `lazy_loaded_images` INT(11) DEFAULT NULL,
  `image_details_json` LONGTEXT,
  `scan_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. LINK ANALYSIS TABLE
CREATE TABLE IF NOT EXISTS `link_analysis` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `website_id` INT(11) NOT NULL,
  `page_url` VARCHAR(255) NOT NULL,
  `total_links` INT(11) DEFAULT NULL,
  `internal_links` INT(11) DEFAULT NULL,
  `external_links` INT(11) DEFAULT NULL,
  `broken_links` INT(11) DEFAULT NULL,
  `nofollow_links` INT(11) DEFAULT NULL,
  `links_details_json` LONGTEXT,
  `scan_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. KEYWORD ANALYSIS TABLE
CREATE TABLE IF NOT EXISTS `keyword_analysis` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `website_id` INT(11) NOT NULL,
  `keyword` VARCHAR(255) NOT NULL,
  `search_volume` INT(11) DEFAULT NULL,
  `keyword_difficulty` INT(11) DEFAULT NULL,
  `current_ranking` INT(11) DEFAULT NULL,
  `scan_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. TECHNICAL SEO TABLE
CREATE TABLE IF NOT EXISTS `technical_seo` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `website_id` INT(11) NOT NULL,
  `crawlability_score` INT(11) DEFAULT NULL,
  `indexability_score` INT(11) DEFAULT NULL,
  `site_speed_score` INT(11) DEFAULT NULL,
  `mobile_friendly_score` INT(11) DEFAULT NULL,
  `schema_markup_valid` TINYINT(1) DEFAULT NULL,
  `has_ssl` TINYINT(1) DEFAULT NULL,
  `internationalization_score` INT(11) DEFAULT NULL,
  `scan_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. COMPETITIVE ANALYSIS TABLE
CREATE TABLE IF NOT EXISTS `competitive_analysis` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `website_id` INT(11) NOT NULL,
  `competitor_url` VARCHAR(255) NOT NULL,
  `comparison_data_json` LONGTEXT,
  `scan_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. AI RECOMMENDATIONS TABLE
CREATE TABLE IF NOT EXISTS `ai_recommendations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `website_id` INT(11) NOT NULL,
  `ai_model_id` INT(11) NOT NULL,
  `summary` TEXT,
  `recommendations_json` LONGTEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`),
  KEY `ai_model_id` (`ai_model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. AI MODELS TABLE
CREATE TABLE IF NOT EXISTS `ai_models` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `type` ENUM('online','local') NOT NULL DEFAULT 'online',
  `api_url` VARCHAR(255) DEFAULT NULL,
  `api_key` VARCHAR(255) DEFAULT NULL,
  `model_name` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `is_default` TINYINT(2) DEFAULT '0',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. SETTINGS TABLE
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(50) NOT NULL,
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT,
  `data_type` ENUM('string','integer','float','boolean','json') NOT NULL DEFAULT 'string',
  `description` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting` (`category`,`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 🏗️ System Architecture & Data Flow

```
 +--------------------------------------------------------------------+
 |                       Web UI / Dashboard                           |
 |               (index.php + Bootstrap 5 + Chart.js)                |
 +--------------------------------------------------------------------+
                                   |
                  HTTP Requests / Internal API Router
                                   |
                                   v
 +--------------------------------------------------------------------+
 |                       PHP Analysis Engine                          |
 |  [ Core Web Vitals ] [ Meta Scanner ] [ Page Structure Parser ]   |
 +--------------------------------------------------------------------+
                                   |
          +------------------------+------------------------+
          |                                                 |
          v                                                 v
 +----------------------------------+            +--------------------+
 |       External APIs / Crawling   |            |   MySQL Database   |
 |  - Google PageSpeed Insights v5  |            |   (ai_seo_tools)   |
 |  - Schema.org Validator API      |            +--------------------+
 +----------------------------------+
```

---

## 📁 Repository Directory Structure

```
seo/
├── index.php                 # Main Dashboard & Interactive Tool Interface
├── config.php                # Database connection & API key configurations (Sanitized)
├── database-structure.txt    # Raw MySQL DDL schema
├── api/                      # API routing endpoints
├── includes/                 # Core engine classes, auth, & caching modules
├── templates/                # UI component templates
├── workers/                  # Async background worker tasks
├── dev_data/                 # Local dev JSON mocks
├── css/                      # Custom styles
├── js/                       # Interactive charts & frontend scripts
└── README.md                 # System documentation
```

---

## ⚙️ Setup & Deployment Guide

1. **Clone Repository**:
   ```bash
   git clone https://github.com/technicguy/Advanced-SEO-Tools.git
   ```
2. **Database Setup**:
   Import the DDL code provided in the **Database Structure** section into your MySQL server (`ai_seo_tools`).
3. **Configure API Credentials (`config.php`)**:
   Open `config.php` and set your credentials:
   ```php
   define('DB_SERVER', '127.0.0.1');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', 'your_mysql_password');
   define('DB_NAME', 'ai_seo_tools');

   // Set your Google PageSpeed API Key
   define('PAGESPEED_API_KEY', 'YOUR_GOOGLE_PAGESPEED_API_KEY');
   ```
4. Access via browser at `http://localhost/seo/index.php`.

---

## 📬 Contact & Support

For support, inquiries, or collaboration, feel free to reach out across any of these channels:

- 📧 **Email:** [technicguy@gmail.com](mailto:technicguy@gmail.com)
- 🌐 **Website:** [https://esanshar.com.np/](https://esanshar.com.np/)
- 📞 **Phone:** [+977 986 445 0173](tel:+9779864450173)
- 💬 **WhatsApp:** [+977 984 470 7950](https://wa.me/9779844707950)
- 💼 **LinkedIn:** [linkedin.com/in/technicguy](https://www.linkedin.com/in/technicguy/)
- 👤 **Facebook:** [facebook.com/imakashgc](https://www.facebook.com/imakashgc)
- 📺 **YouTube Channels:**
  - 🎵 **Sound & Frequency:** [Mystic Sound Journeys](https://www.youtube.com/@MysticSoundJourneys?sub_confirmation=1)
  - 👶 **Kids Content:** [MummaBaba](https://www.youtube.com/@MummaBaba?sub_confirmation=1)
