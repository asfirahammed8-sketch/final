# WP Admin Reset System

A headless administration synchronization reset tool. This system allows developers and administrators to reset WordPress database options and Custom Post Types directly back to their frontend-visible static defaults.

---

## Architecture of Reset System

Each custom WordPress plugin exposes a secure JSON REST API endpoint capable of accepting a `POST` request to restore the database options or custom posts back to the canonical frontend JSON structures defined in the `defaults/` folder.

### 1. Reset Endpoints

| Service Area | Method | Endpoint | Source File | DB/Data Operation |
|---|---|---|---|---|
| **Home Settings** | `POST` | `/wp-json/wp-sync/v1/reset/home` | `plugins/home/home-plugin.php` | Deletes existing cache transient and overrides `hero_title`, `hero_subtitle`, `hero_cta_text`, `stats_total_students`, and `stats_active_courses` options. |
| **Course Catalog** | `POST` | `/wp-json/wp-sync/v1/reset/courses` | `plugins/course/course-plugin.php` | Purges all existing custom post type `course` entries. Re-inserts entries matching the static fallback array, setting metadata keys and marking the first course as featured. |
| **Event Calendar** | `POST` | `/wp-json/wp-sync/v1/reset/events` | `plugins/event/event-plugin.php` | Purges all existing custom post type `event` entries. Re-inserts event nodes matching the static defaults, setting metadata keys and marking the first event as upcoming. |

---

## Reset Operations Format (Payloads)

### REST Response: Successful Home Reset
```json
{
  "status": "success",
  "message": "Home settings successfully reset to defaults.",
  "reset_fields": [
    "hero_title",
    "hero_subtitle",
    "hero_cta_text",
    "stats_total_students",
    "stats_active_courses"
  ]
}
```

### REST Response: Successful Course Catalog Reset
```json
{
  "status": "success",
  "message": "Course catalog reset to static data contract.",
  "courses_restored": [
    "mastering-react-typescript",
    "advanced-wordpress-plugins"
  ]
}
```

### REST Response: Successful Events Reset
```json
{
  "status": "success",
  "message": "Events calendar reset to static data contract.",
  "events_restored": [
    "global-web-dev-summit-2026",
    "wp-decoupled-headless-workshop"
  ]
}
```
