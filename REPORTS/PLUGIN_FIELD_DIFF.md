# Plugin Field Diff

| Plugin | Field | UI Used | Action | Notes / Reasons |
|---|---|---|---|---|
| **home** | `hero_title` | **Yes** | **KEEP** | Directly rendered as heading in the Home component's Hero section. |
| **home** | `hero_subtitle` | **Yes** | **KEEP** | Directly rendered as subtitle paragraph in the Home component's Hero section. |
| **home** | `hero_cta_text` | **Yes** | **KEEP** | Directly rendered inside the Call To Action button. |
| **home** | `stats_total_students` | **Yes** | **KEEP** | Rendered in stats counter box on Home page. |
| **home** | `stats_active_courses` | **Yes** | **KEEP** | Rendered in stats counter box on Home page. |
| **home** | `admin_seo_meta_keywords` | **No** | **REMOVE** | ❌ UNUSED_IN_UI. WordPress-internal or unused developer field leaking to the API. |
| **home** | `internal_developer_notes` | **No** | **REMOVE** | ❌ UNUSED_IN_UI. Leaks notes meant only for developers or back-end administrators. |
| **course** | `_course_duration` | **Yes** | **KEEP** | Rendered on both `CourseCard` components and `CourseDetail` page. |
| **course** | `_course_rating` | **Yes** | **KEEP** | Rendered on `CourseCard` component to display star ratings. |
| **course** | `_course_original_price`| **Yes** | **KEEP** | Rendered on `CourseCard` component (strikethrough price). |
| **course** | `_course_discount_price`| **Yes** | **KEEP** | Rendered on `CourseCard` component as the current active price. |
| **course** | `_course_category` | **Yes** | **KEEP** | Rendered as a badge label on `CourseCard`. |
| **course** | `_course_student_limit` | **Yes** | **KEEP** | Rendered in the sidebar/specifications section of the `CourseDetail` page. |
| **course** | `_course_pdf_url` | **Yes** | **KEEP** | Rendered as an external syllabus download link on the `CourseDetail` page. |
| **course** | `_course_pdf_file_name` | **No** | **REMOVE** | ❌ UNUSED_IN_UI. Raw file names should not be exposed or managed on frontend. |
| **course** | `_course_seo_meta` | **No** | **REMOVE** | ❌ UNUSED_IN_UI. Internal metadata. Should be removed to avoid unnecessary REST overhead. |
| **course** | `_course_internal_notes`| **No** | **REMOVE** | ❌ UNUSED_IN_UI. Leak of administrative or teacher-only course annotations. |
| **event** | `_event_date` | **Yes** | **KEEP** | Rendered in calendar details of event listings and details pages. |
| **event** | `_event_location` | **Yes** | **KEEP** | Rendered as text string on both event list and event details pages. |
| **event** | `_event_short_description`|**Yes** | **KEEP** | Rendered inside the event list preview cards. |
| **event** | `_event_speaker_name` | **Yes** | **KEEP** | Rendered as "Speaker" on the event details page. |
| **event** | `_event_capacity` | **Yes** | **KEEP** | Rendered on the event details page. |
| **event** | `_event_ticket_price` | **Yes** | **KEEP** | Rendered as the purchase cost on event cards and detail sections. |
| **event** | `_event_internal_notes`| **No** | **REMOVE** | ❌ UNUSED_IN_UI. Internal admin annotations leaking into the REST payloads. |
| **event** | `_event_speaker_email` | **No** | **REMOVE** | ❌ UNUSED_IN_UI. Privacy concern: exposing raw email addresses over public REST APIs. |
