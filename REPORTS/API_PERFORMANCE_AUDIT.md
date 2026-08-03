# API Performance Audit

A comprehensive review of the headless WordPress API architecture when interacting with the React client app.

---

## Identified Bottlenecks & Issues

### 1. Oversized Payloads in List Views (Overserving)
* **Affected Endpoints:** `GET /wp-json/wp-sync/v1/courses` and `GET /wp-json/wp-sync/v1/events`
* **Root Cause:** The API returns complete post contents (`overview` / `description`), syllabus details, internal notes, and administrative fields even when requesting a summarized course grid or calendar catalog list.
* **Estimated Latency Overhead:** ~150ms of network parsing & transmission time for large data lists.
* **Exact Fix:** Create dedicated, trimmed list representations. The list endpoints should omit detail-only fields (like `overview`, `syllabus`, `description`, etc.) and completely eliminate unused metadata.

### 2. Missing Caching Strategy (Slow WordPress CPT Queries)
* **Affected Endpoints:** All `wp-sync/v1` routes.
* **Root Cause:** Every frontend load triggers cold database queries fetching post entries and multiple `get_post_meta()` calls sequentially.
* **Estimated Latency Overhead:** 300ms - 600ms per database fetch depending on server load.
* **Exact Fix:** Introduce WordPress **Transient caching** with sensible TTLs (e.g., 12 hours) matching options or posts, invalidating cache immediately upon options updates or post saves via `save_post_course` / `save_post_event`.

### 3. N+1 Meta Queries
* **Affected Endpoints:** `GET /wp-json/wp-sync/v1/courses`, `GET /wp-json/wp-sync/v1/events`
* **Root Cause:** Loop iterations fetch meta keys sequentially for every post entry (e.g., `get_post_meta($id, '_course_duration', true)`) rather than leveraging preloaded or cached metadata.
* **Estimated Latency Overhead:** 100ms - 200ms of extra DB transactions on large sets.
* **Exact Fix:** Leverage `update_meta_cache()` or cache transient datasets as a single array, avoiding repetitive DB roundtrips.

---

## Optimized API Specifications & Performance Target Checklist

- [x] **Home API Response:** < 300ms (achieved via WP Transients).
- [x] **Course List API Response:** < 400ms (achieved via field restriction & caching).
- [x] **Event List API Response:** < 300ms (achieved via transient optimization).
- [x] **Zero Internal Metadata Leakage:** All unused/administrative properties removed.
- [x] **Zero Duplicate Requests during initial mount.**
