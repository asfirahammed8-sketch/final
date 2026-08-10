# Plugin Map

| WordPress Plugin | Custom Post Type / Option Key | REST Base Endpoint | Component / Page Consumer |
|---|---|---|---|
| **home** | Options (`hero_title`, `hero_subtitle`, `hero_cta_text`, `stats_total_students`, `stats_active_courses`) | `/wp-json/wp-sync/v1/home` | `src/pages/Home.tsx` |
| **course** | Custom Post Type `course` | `/wp-json/wp-sync/v1/courses`, `/wp-json/wp-sync/v1/courses/<slug>` | `src/pages/Home.tsx`, `src/pages/CourseCatalog.tsx`, `src/pages/CourseDetail.tsx`, `src/components/CourseCard.tsx` |
| **event** | Custom Post Type `event` | `/wp-json/wp-sync/v1/events`, `/wp-json/wp-sync/v1/events/<slug>` | `src/pages/Home.tsx`, `src/pages/Events.tsx`, `src/pages/EventDetail.tsx` |
