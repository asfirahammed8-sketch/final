# Footer Database Mapping & Architecture Documentation

This document details every single piece of content appearing in the global footer (`includes/footer.php`) and its exact source in the MySQL database.

---

## 1. Summary of Database Mapping

| Footer Section / Element | Database Table | Column / Setting Key | Example Value in Database | How It Is Fetched |
| :--- | :--- | :--- | :--- | :--- |
| **Head Office Address** | `site_settings` | `setting_group = 'contact'`, `setting_key = 'contact_address'` | `Momtaz Plaza (4th Floor)\nHouse # 07, Road # 04\nDhanmondi, Dhaka-1205` | `get_setting('contact_address')` |
| **Phone Numbers / Hotline** | `site_settings` | `setting_group = 'contact'`, `setting_key = 'contact_phones'` | `["+880 1777308777", "+880 1624666000", "+880 1624888444", "+880 1966177177"]` | `get_setting('contact_phones')` or `get_setting('contact')['phones']` |
| **Contact Email** | `site_settings` | `setting_group = 'contact'`, `setting_key = 'contact_email'` | `info@creativeitinstitute.com` | `get_setting('contact_email')` |
| **Admission CTA Heading** | `site_settings` | `setting_group = 'footer'`, `setting_key = 'footer_cta_title'` | `Admission Is Going on` | `get_setting('footer_cta_title')` |
| **Admission CTA Subtitle** | `site_settings` | `setting_group = 'footer'`, `setting_key = 'footer_cta_desc'` | `Enroll in any online or offline course now — take one step towards a competent career.` | `get_setting('footer_cta_desc')` |
| **Popular Courses** | `courses` | `title`, `slug`, `status = 'active'`, `(priority = 'top' OR is_featured = 1)` | `Professional Graphic Design`, `UI/UX Design Masterclass`, etc. | PDO Query with `GROUP BY title` to guarantee zero duplicate titles or malformed slugs |
| **Student Work (Others)** | `projects` | `title`, `slug`, `status = 'published'` | `Foodie - On-Demand Food Delivery Mobile App`, `ShopSphere`, etc. | `SELECT title, slug FROM projects WHERE status = 'published' ORDER BY sort_order ASC, created_at DESC LIMIT 4` |
| **Success Stories (Others)** | `success_stories` | `student_name`, `headline`, `is_active = 1` | `Mehedi Hasan`, `Farhana Akter`, `Kamrul Islam` | `SELECT student_name, headline FROM success_stories WHERE is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT 3` |
| **Quick Links** | Dynamic Routes | PHP Helpers | Home, Courses, Projects, Stories, FAQ, Contact, Privacy, Sitemap | Generated via `base_url()` — zero hardcoded hostnames |
| **Social Media: Facebook** | `site_settings` | `setting_group = 'social'`, `setting_key = 'social_facebook'` | `https://www.facebook.com/CreativeITInstitute` | `get_setting('social_facebook')` |
| **Social Media: YouTube** | `site_settings` | `setting_group = 'social'`, `setting_key = 'social_youtube'` | `https://www.youtube.com/@CreativeITInstitute` | `get_setting('social_youtube')` |
| **Social Media: LinkedIn** | `site_settings` | `setting_group = 'social'`, `setting_key = 'social_linkedin'` | `https://www.linkedin.com/school/creativeitinstitute/` | `get_setting('social_linkedin')` |
| **Social Media: Instagram** | `site_settings` | `setting_group = 'social'`, `setting_key = 'social_instagram'` | `https://www.instagram.com/creativeit_institute` | `get_setting('social_instagram')` |
| **Social Media: Twitter (X)** | `site_settings` | `setting_group = 'social'`, `setting_key = 'social_twitter'` | `https://twitter.com/creativeit_inst` | `get_setting('social_twitter')` |
| **Social Media: TikTok** | `site_settings` | `setting_group = 'social'`, `setting_key = 'social_tiktok'` | `https://www.tiktok.com/@creativeitinstitute` | `get_setting('social_tiktok')` |
| **Site Name & Brand** | `site_settings` | `setting_group = 'general'`, `setting_key = 'site_name'` | `Creative IT Institute - DreamIT` | `site_brand()` helper |
| **Logo** | `site_settings` | `setting_group = 'general'`, `setting_key = 'logo_url'` | URL or fallback SVG | `get_setting('general')['logo_url']` |
| **Copyright Year** | Runtime | PHP `date('Y')` | Current Year (e.g., `2026`) | Dynamic PHP |

---

## 2. Why Was It Previously Showing Hardcoded Fallbacks?

In `includes/footer.php`, the code was calling:
```php
$contactSettings = get_setting('contact', []);
```
However, in the database table `site_settings`, the rows were stored under individual keys: `contact_address`, `contact_email`, `contact_phones`, and `contact_hotline`. Because the key was not `'contact'`, `get_setting('contact')` previously returned an empty array, which forced PHP to fall back to hardcoded default values.

### The Fix Implemented:
1. **Enhanced `get_setting()` in `includes/helpers.php`**:
   - Supports both direct key lookups (`get_setting('contact_address')`) and group lookups (`get_setting('contact')`).
   - Group lookups automatically strip the prefix so `$contactSettings['address']` or `$contactSettings['phones']` resolve dynamically from the database.
2. **Synchronized `site_settings`**:
   - All contact data (multiple phone numbers, address, email), CTA text, and social URLs are actively stored in `site_settings`.
3. **Course Deduplication**:
   - In `courses`, older duplicate entries with broken slugs (`rofessional-raphic-esign`) were filtered out by grouping by `title` and picking the highest ID with a clean slug, preventing duplicate course links in the footer.

---

## 3. Centering the Contact Column & Responsive Grid Behavior

In `frontend/assets/css/footer.css`:
- **Desktop (4 Columns, 1 Row)**:
  - `.footer-nav-grid > div` and `.footer-nav-grid > div:first-child` are centered (`display: flex; flex-direction: column; align-items: center; text-align: center;`).
  - Contact column content (`.contact-item-row`) is centered inside the column container with readable icon-and-text alignment (`max-width: 280px; margin: 0 auto;`).
- **Tablet / Medium Screens (`<= 1120px`)**:
  - The 4 columns cleanly break into **2 Rows (2 Columns per Row)**:
    - **Row 1**: Column 1 (Contact, Centered) and Column 2 (Quick Links, Centered).
    - **Row 2**: Column 3 (Popular Courses, Centered) and Column 4 (Others, Centered).
  - All content remains centered, balanced, and visually harmonious.
- **Mobile Screens (`<= 680px`)**:
  - Automatically collapses to 1 column where all sections are centered vertically.

---

## 4. Contact Us Page Database Mapping & Admin Panel Specification

This section outlines every data item displayed on the Contact Us page (`frontend/pages/contact.php`), where it originates in the database, and the exact form controls required for the future Admin Panel to manage it.

### 4.1 Contact Page Data Mapping Table

| Contact Page Section | UI Element / Field | Database Table | Group / Key | Data Type | Current Database Value | Admin Panel Input Control |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Hero Banner** | Page Title | `site_settings` | `contact_hero_title` (group: `contact`) | `varchar / string` | `Contact Us` | Text Input (`maxlength: 100`) |
| **Hero Banner** | Page Subtitle | `site_settings` | `contact_hero_subtitle` (group: `contact`) | `text` | `Have questions regarding our professional IT training programs, course admissions, or campus lab facilities? Get in touch with our expert academic counselors or visit any of our physical campuses.` | Textarea (`rows: 3`) |
| **Campus Info Card** | Head Office / Campus Name | `site_settings` | `contact_head_office_name` (group: `contact`) | `varchar / string` | `Dhanmondi Campus` | Text Input (`placeholder: Campus Name`) |
| **Campus Info Card** | Campus Badge / Title | `site_settings` | `contact_head_office_title` (group: `contact`) | `varchar / string` | `Head Office` | Text Input (`placeholder: e.g. Head Office / Main Branch`) |
| **Campus Info Card** | Full Physical Address | `site_settings` | `contact_address` (group: `contact`) | `text` | `Momtaz Plaza (4th Floor)\nHouse # 07, Road # 04\nDhanmondi, Dhaka-1205` | Textarea (`rows: 4, newline separated`) |
| **Campus Info Card** | Area / Sub-district | `site_settings` | `contact_head_office_area` (group: `contact`) | `varchar / string` | `Dhanmondi` | Text Input (`placeholder: Dhanmondi`) |
| **Campus Info Card** | City / Division | `site_settings` | `contact_head_office_city` (group: `contact`) | `varchar / string` | `Dhaka` | Text Input (`placeholder: Dhaka`) |
| **Campus Info Card** | All Phone Numbers | `site_settings` | `contact_phones` (group: `contact`) | `JSON array of strings` | `["+880 1777308777", "+880 1624666000", "+880 1624888444", "+880 1966177177"]` | Dynamic Multi-Tag / Repeater Input (or newline-separated textarea converted to JSON) |
| **Campus Info Card** | Primary Email | `site_settings` | `contact_email` (group: `contact`) | `varchar / email` | `info@creativeitinstitute.com` | Email Input (`type="email"`) |
| **Campus Info Card** | Google Maps Share URL | `site_settings` | `contact_google_maps_share_url` (group: `contact`) | `varchar / url` | `https://maps.app.goo.gl/nhDnc5QjJ9MgQ97f9` | URL Input (`type="url"`) |
| **Quick Contact Cards** | Admission Hotline | `site_settings` | `contact_hotline` (group: `contact`) | `varchar / tel` | `+880 1777308777` | Phone / Tel Input (`type="tel"`) |
| **Quick Contact Cards** | Official Email | `site_settings` | `contact_email` (group: `contact`) | `varchar / email` | `info@creativeitinstitute.com` | Email Input |
| **Quick Contact Cards** | Campus Hours | `site_settings` | `contact_campus_hours` (group: `contact`) | `varchar / string` | `Sat – Thu: 9 AM – 8 PM` | Text Input (`placeholder: e.g. Sat – Thu: 9 AM – 8 PM`) |
| **Quick Contact Cards** | Head Campus Display | `site_settings` | Formed from `contact_head_office_area` + `contact_head_office_city` | `composite` | `Dhanmondi, Dhaka` | Controlled via Area & City inputs |
| **Inquiry Form** | Form Heading | `site_settings` | `contact_form_title` (group: `contact`) | `varchar / string` | `Send Us a Message` | Text Input |
| **Inquiry Form** | Form Subheading | `site_settings` | `contact_form_desc` (group: `contact`) | `text` | `Fill out the inquiry form below and an academic counselor will reach out to guide you within 24 hours.` | Textarea (`rows: 2`) |
| **Inquiry Form** | Department Dropdown Options | `departments` | `name`, `slug`, `is_active = 1` | `relational table` | Graphic & Multimedia, Web & Software, Digital Marketing, etc. | Managed via Departments CRUD module |
| **Inquiry Submissions** | Stored Inquiries / Leads | `enquiries` | Table: `enquiries` (`name`, `phone`, `email`, `department`, `message`, `ip_address`, `status`) | `relational table` | Incoming leads from users | Managed via Admin Inquiries / CRM Inbox |
| **Location Map Embed** | Google Maps Iframe Embed URL | `site_settings` | `contact_google_maps_embed_url` (group: `contact`) | `text / url` | `https://www.google.com/maps/embed?pb=!1m18!...` | Textarea / URL Input (`placeholder: Embed iframe URL`) |
| **SEO Meta & Schema** | Title, Description, Schema JSON | `page_seo` / `site_settings` | `slug = 'contact'` | `varchar / text` | Title & Meta description for SEO | Managed via SEO Settings Module |

---

### 4.2 Discrepancy & Tracking Fixes Implemented

Before this audit, several contact settings were appearing from the wrong place or relying on hardcoded fallbacks:

1. **Address Key Bypassing in `contact.php`**:
   - *Problem*: In `site_settings`, the address is stored as `contact_address` (aliasing to `address`). However, `contact.php` originally checked only `$contactSettings['head_office_address']`. Because that specific key did not exist in the database, `contact.php` ignored the database value and displayed a hardcoded string.
   - *Fix*: Updated `contact.php` to look up `$contactSettings['head_office_address'] ?? $contactSettings['address'] ?? $contactSettings['contact_address']`. If the admin updates the address in the database, it instantly reflects on both the footer and the contact page.

2. **Admission Hotline Ignored**:
   - *Problem*: `site_settings` has a dedicated `contact_hotline` key (`+880 1777308777`), but `contact.php` bypassed it by taking `$phones[0]`.
   - *Fix*: Updated `contact.php` to prioritize `$contactSettings['hotline'] ?? $contactSettings['contact_hotline'] ?? $phones[0]`.

3. **Missing Keys Added to Database**:
   - *Problem*: Keys such as `contact_campus_hours`, `contact_google_maps_embed_url`, `contact_google_maps_share_url`, `contact_head_office_name`, `contact_head_office_area`, `contact_head_office_city`, `contact_hero_title`, `contact_hero_subtitle`, `contact_form_title`, and `contact_form_desc` were only in PHP code fallbacks and did not exist in `site_settings`.
   - *Fix*: Initialized all these keys in the `site_settings` table under group `'contact'`. Any update in the database or future admin panel now immediately controls these fields dynamically.

4. **Undefined `:ip` Variable in `api/enquiry-submit.php`**:
   - *Problem*: Line 126 was binding `:ip => $ip`, but the client IP variable was named `$clientIp` on line 46, which caused an undefined variable warning during form submissions.
   - *Fix*: Changed `:ip => $ip` to `:ip => $clientIp`.

---

### 4.3 Recommended Admin Panel Form Structure

For the upcoming Admin Panel, a dedicated **"Contact & Campus Settings"** page should be organized into the following tabs or fieldsets:

```
[ Tab 1: General & Hero ]
  ├── Hero Title (text) -> contact_hero_title
  └── Hero Subtitle (textarea) -> contact_hero_subtitle

[ Tab 2: Head Office & Campus ]
  ├── Campus Name (text) -> contact_head_office_name
  ├── Campus Badge (text) -> contact_head_office_title
  ├── Area / Thana (text) -> contact_head_office_area
  ├── City (text) -> contact_head_office_city
  ├── Full Address (textarea) -> contact_address
  └── Campus Visiting Hours (text) -> contact_campus_hours

[ Tab 3: Communication Channels ]
  ├── Admission Hotline (tel) -> contact_hotline
  ├── Primary Email (email) -> contact_email
  └── All Contact Phones (tag/list editor) -> contact_phones (JSON array)

[ Tab 4: Maps & Embeds ]
  ├── Google Maps Embed Iframe URL (text/url) -> contact_google_maps_embed_url
  └── Google Maps Share Link (url) -> contact_google_maps_share_url

[ Tab 5: Inquiry Form Settings ]
  ├── Form Card Title (text) -> contact_form_title
  └── Form Card Description (textarea) -> contact_form_desc
```

---

## 5. Header & Mobile Navigation Database Mapping & Admin Panel Specification

This section details every data element appearing in the global header (`includes/header.php`) and the mobile drawer navigation, their database sources, and the admin panel settings required to manage them.

### 5.1 Header & Mobile Navigation Data Mapping Table

| Header / Drawer Section | UI Element / Field | Database Table | Group / Key | Data Type | Current Database Value | Admin Panel Input Control |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Branding & Logo** | Site Name / Brand Text | `site_settings` | `site_name` (group: `general`) | `varchar(255)` | `Creative IT Institute - DreamIT` | Text Input (`placeholder: Site Brand Name`) |
| **Branding & Logo** | Tagline | `site_settings` | `site_tagline` (group: `general`) | `varchar(255)` | `Empowering Next-Gen Leaders with Practical Tech Skills` | Text Input |
| **Branding & Logo** | Custom Logo Image URL | `site_settings` | `logo_url` (group: `general`) | `varchar(500)` / URL | `NULL` (Uses dynamic SVG brandmark fallback) | Image Upload / File Picker + URL Input |
| **Header CTA Button** | CTA Button Label | `site_settings` | `header_cta_text` (group: `general`) | `varchar(100)` | `Contact Us` | Text Input (`placeholder: Contact Us`) |
| **Header CTA Button** | CTA Button Link Target | `site_settings` | `header_cta_url` (group: `general`) | `varchar(255)` | `contact/` | Text Input / Internal Route Selector |
| **Course Dropdown (Desktop & Mobile)** | Active Department List | `departments` | `name`, `slug`, `sort_order`, `is_active = 1` | `relational table` | Top 6 active departments | Managed via Departments CRUD module |
| **Course Dropdown (Desktop & Mobile)** | Max Departments Limit | `site_settings` | `header_nav_courses_limit` (group: `general`) | `integer` | `6` | Number Input (`min: 3, max: 12`) |
| **Mobile Drawer CTA** | Direct Hotline Dial | `site_settings` | `contact_hotline` (group: `contact`) | `varchar(50)` | `+880 1777308777` | Managed under Contact Settings (Hotline) |
| **Explore Quick Chips** | Student Work, Gallery, FAQ | Static Routes | `projects/`, `gallery/`, `faq/` | `internal routes` | Fast-access mobile chips | Quick exploration links |
| **Utility Footer Links** | Privacy & Sitemap | Static Routes | `privacy/`, `sitemap/` | `internal routes` | Compact micro-links | Utility links at bottom of drawer |

---

### 5.2 Mobile Navigation UX & Flow Redesign

Previously, the mobile drawer rendered an overwhelming, single-column flat list of 15+ links, stretching over 750px vertically and requiring 3 full scroll screens on mobile devices.

#### The Improvements Implemented:
1. **Collapsible Courses Accordion**:
   - Replaced flat course links with an interactive accordion button (`#mobileCoursesToggle`).
   - Displays department count badge (`6 Depts`) with an animated indicator chevron.
   - Smoothly expands/collapses the sub-menu of active departments without page reloading or closing the drawer.
   - When collapsed, the drawer height is reduced by over **55%**, fitting on mobile screens without forced scrolling.
2. **Compact 3-Column Quick-Explore Chips**:
   - Replaced bulky full-width rows for secondary pages (`Student Work`, `Gallery`, `FAQ`) with a compact, modern 3-column chip grid featuring clean SVG icons.
3. **Dual Quick-Action Bar**:
   - Primary `Contact Us` CTA button.
   - Secondary 1-tap `Call Hotline` dialer (`tel:`) styled as a quick-call button with phone icon.
4. **Click-Outside Dismiss & Touch-Friendly Scrolling**:
   - Drawer automatically closes if user taps outside the drawer overlay on mobile screens.
   - Smooth slide-down animation (`@keyframes mobileDrawerSlide`).

---

### 5.3 Recommended Admin Panel "General & Navigation Settings" Structure

```
[ Tab: Site Identity & Branding ]
  ├── Site Name (text) -> site_name
  ├── Site Tagline (text) -> site_tagline
  └── Logo Image (upload / url) -> logo_url

[ Tab: Header Navigation ]
  ├── Max Course Dropdown Items (number, default: 6) -> header_nav_courses_limit
  ├── Header CTA Button Label (text) -> header_cta_text
  └── Header CTA Destination URL (text) -> header_cta_url
```
