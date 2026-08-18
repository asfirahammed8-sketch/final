# Static Data Contract

Mapping fallback static data structures used when APIs fail or load.

## Fallback Files

### 1. Home Fallback (`src/data/fallback-home.ts`)
- **Exported object:** `fallbackHomeData`
- **Fields:**
  - `hero_title`
  - `hero_subtitle`
  - `hero_cta_text`
  - `stats_total_students`
  - `stats_active_courses`
- **Used by:** `src/pages/Home.tsx` via `src/api/home.ts`
- **API Endpoint Equivalent:** GET `/wp-json/wp-sync/v1/home`

### 2. Courses Fallback (`src/data/fallback-courses.ts`)
- **Exported array:** `fallbackCourses`
- **Fields:**
  - `title`, `slug`, `thumbnail`, `short_description`, `duration`, `rating`, `original_price`, `discount_price`, `category`, `overview`, `syllabus`, `instructor`, `student_limit`, `pdf_url`
- **Used by:** `src/pages/CourseCatalog.tsx`, `src/pages/CourseDetail.tsx`, and `src/components/CourseCard.tsx` via `src/api/course.ts`
- **API Endpoint Equivalent:** GET `/wp-json/wp-sync/v1/courses`, `/wp-json/wp-sync/v1/courses/:slug`

### 3. Events Fallback (`src/data/fallback-events.ts`)
- **Exported array:** `fallbackEvents`
- **Fields:**
  - `title`, `slug`, `event_date`, `location`, `short_description`, `description`, `speaker_name`, `capacity`, `ticket_price`
- **Used by:** `src/pages/Events.tsx` and `src/pages/EventDetail.tsx` via `src/api/event.ts`
- **API Endpoint Equivalent:** GET `/wp-json/wp-sync/v1/events`, `/wp-json/wp-sync/v1/events/:slug`
