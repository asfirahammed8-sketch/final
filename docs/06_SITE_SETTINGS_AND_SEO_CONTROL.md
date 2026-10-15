# 06. Site Settings Engine & Global SEO Control Suite

## 1. Overview & Objectives
The DreamIT Platform requires a centralized management engine for:
1. **Site-Wide Branding & Identity** (Logo, favicon, brand name, tagline, copyright notice).
2. **Contact & Admission Desks** (Hotlines, WhatsApp direct link, email addresses, campus physical addresses, opening hours).
3. **Global Header & Navigation Controls** (Brand accent colors, top notification bar announcement, live CTA button text and destination URL).
4. **Autonomous SEO & Schema.org Controller** (Global OpenGraph images, Twitter cards, meta descriptions, Google Search Console verification meta tags, Google Analytics/Tag Manager injection, and per-page SEO overrides via `page_seo` table).

---

## 2. Key-Value Storage Architecture (`site_settings`)

Settings are stored in the database as an indexed key-value pair system to allow zero-downtime additions of new settings without schema migrations:

```sql
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(191) NOT NULL UNIQUE,
  `value` LONGTEXT NULL,
  `type` ENUM('string', 'number', 'boolean', 'json', 'textarea') NOT NULL DEFAULT 'string',
  `group` VARCHAR(50) NOT NULL DEFAULT 'general',
  `description` VARCHAR(255) NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_settings_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Settings Categorization & Control Matrix

### Group 1: Branding & Visual Identity (`general`)
| Setting Key | Type | Description | Frontend Utilization |
| :--- | :--- | :--- | :--- |
| `site_brand` | string | Primary Institution Name (e.g., `Creative IT Institute Naogaon`) | Header logo text, footer copyright, SEO title fallback. |
| `site_tagline` | string | Institution slogan (e.g., `Empowering Youth Through Technology`) | Hero subtitles, meta descriptions, browser tab titles. |
| `site_logo_url` | string | Transparent SVG or PNG URL | Top header brand emblem. |
| `site_favicon_url`| string | 32x32 / 180x180 favicon URL | Browser tab icon and apple-touch-icon. |
| `header_cta_text` | string | Main conversion button text (e.g. `Free Seminar / Admission`) | Header top right action pill. |
| `header_cta_url` | string | Destination URL for the header button | Redirects prospective students to admission or contact form. |

### Group 2: Contact Channels & Campus Locations (`contact`)
| Setting Key | Type | Description | Frontend Utilization |
| :--- | :--- | :--- | :--- |
| `contact_hotlines` | string | Comma-separated phone numbers | Desktop quick-contact pills, header hotline, footer links (`tel:`). |
| `contact_whatsapp` | string | Direct WhatsApp mobile number (with country code `+880...`) | Floating WhatsApp chat badge and inquiry forms. |
| `contact_emails` | string | Primary and admission emails | Footer contact list, auto-responder sender, inquiry dispatch. |
| `contact_address` | textarea | Head office / main campus multi-line address | Footer office address block, Contact Us hero card. |
| `office_hours` | string | Working days and hours (e.g. `Sat - Thu: 9 AM - 8 PM`) | Contact Us page operating hours badge. |
| `google_maps_embed`| textarea | Google Maps iframe embed URL | Contact Us interactive campus map viewer. |

### Group 3: Social Media Presence (`social`)
| Setting Key | Type | Description | Frontend Utilization |
| :--- | :--- | :--- | :--- |
| `facebook_url` | string | Official Facebook page URL | Header top bar, footer social icons. |
| `youtube_url` | string | YouTube channel link | Video trailer links, footer icon. |
| `linkedin_url` | string | LinkedIn company page | Footer corporate profile icon. |
| `instagram_url`| string | Instagram page link | Footer social link list. |

### Group 4: Global SEO & Search Console Integrations (`seo`)
| Setting Key | Type | Description | Frontend Utilization |
| :--- | :--- | :--- | :--- |
| `seo_default_title` | string | Global fallback `<title>` suffix | Appends `| Creative IT Institute` to all pages. |
| `seo_default_desc` | textarea | Global fallback meta description | Injected if page-specific meta is not defined. |
| `seo_default_og_image`| string | 1200x630 pixel OpenGraph share image | Rendered on Facebook/WhatsApp social link previews. |
| `google_site_verification`| string | Google Search Console verification code | Injected as `<meta name="google-site-verification" content="...">`. |
| `google_analytics_id`| string | Google Tag / GA4 Measurement ID (`G-XXXXXXXXXX`) | Autonomous lightweight gtag script injection before `</head>`. |

---

## 4. Per-Page Granular SEO Engine (`page_seo`)

In addition to site-wide defaults, specific high-traffic routes (`/`, `/about`, `/courses`, `/contact`, `/success-stories`, `/gallery`, `/faq`) have explicit entries in `page_seo`:

```sql
CREATE TABLE IF NOT EXISTS `page_seo` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `page_slug` VARCHAR(100) NOT NULL UNIQUE,
  `meta_title` VARCHAR(255) NOT NULL,
  `meta_description` TEXT NOT NULL,
  `meta_keywords` VARCHAR(500) NULL,
  `canonical_url` VARCHAR(500) NULL,
  `og_image` VARCHAR(500) NULL,
  `schema_type` VARCHAR(50) DEFAULT 'WebPage',
  `custom_schema_json` LONGTEXT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4.1. Admin SEO Card Interface
The Admin SEO Console provides a dedicated card for each registered page route with:
- **Live Google Search Snippet Simulation**: Shows how the title, clean breadcrumb URL, and snippet appear in desktop and mobile Google results in real time.
- **Title Length Bar**: Dynamic indicator (Optimal: 50-60 characters; Amber: >60; Red: >70).
- **Description Length Bar**: Dynamic indicator (Optimal: 140-160 characters).
- **Social Preview Toggle**: Switches snippet preview between Google SERP, Facebook OpenGraph, and Twitter Cards.
- **Custom JSON-LD Schema Editor**: Allows validating structured data with instant JSON syntax validation.
