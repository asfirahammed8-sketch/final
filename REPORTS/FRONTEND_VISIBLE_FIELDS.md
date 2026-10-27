# Frontend Visible Fields

Exact JSX-rendered fields traced from routes down to API response definitions. Unrendered API data is marked with `❌ UNUSED_IN_UI`.

---

## Home Hero & Stats
* Traced from: `src/pages/Home.tsx` -> `getHomeData()` -> `/wp-json/wp-sync/v1/home`

* **hero_title**
* **hero_subtitle**
* **hero_cta_text**
* **stats_total_students**
* **stats_active_courses**
* ❌ `admin_seo_meta_keywords` (UNUSED_IN_UI)
* ❌ `internal_developer_notes` (UNUSED_IN_UI)

---

## Course Card
* Traced from: `src/components/CourseCard.tsx` -> Reused by `Home` and `CourseCatalog` -> `getCourses()` -> `/wp-json/wp-sync/v1/courses`

* **title**
* **slug**
* **thumbnail**
* **short_description**
* **duration**
* **rating**
* **original_price**
* **discount_price**
* **category**

---

## Course Detail
* Traced from: `src/pages/CourseDetail.tsx` -> `getCourseBySlug(slug)` -> `/wp-json/wp-sync/v1/courses/:slug`

* **title**
* **overview**
* **syllabus**
* **instructor**
* **duration**
* **student_limit**
* **pdf_url**
* ❌ `pdf_file_name` (UNUSED_IN_UI)
* ❌ `seo_meta` (UNUSED_IN_UI)
* ❌ `internal_notes` (UNUSED_IN_UI)

---

## Event Card / List
* Traced from: `src/pages/Events.tsx` -> `getEvents()` -> `/wp-json/wp-sync/v1/events`

* **title**
* **event_date**
* **location**
* **short_description**
* **ticket_price**
* ❌ `description` (UNUSED_IN_UI in list view)
* ❌ `speaker_name` (UNUSED_IN_UI in list view)
* ❌ `capacity` (UNUSED_IN_UI in list view)
* ❌ `internal_notes` (UNUSED_IN_UI)
* ❌ `speaker_email` (UNUSED_IN_UI)

---

## Event Detail
* Traced from: `src/pages/EventDetail.tsx` -> `getEventBySlug(slug)` -> `/wp-json/wp-sync/v1/events/:slug`

* **title**
* **event_date**
* **location**
* **description**
* **speaker_name**
* **capacity**
* **ticket_price**
* ❌ `internal_notes` (UNUSED_IN_UI)
* ❌ `speaker_email` (UNUSED_IN_UI)
