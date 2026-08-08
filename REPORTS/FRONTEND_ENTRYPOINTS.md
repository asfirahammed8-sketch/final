# Frontend Entrypoints

## Pages and Routing

1. **Home (`/`)**
   - Entrypoint: `src/pages/Home.tsx`
   - Consumes: `/wp-json/wp-sync/v1/home`, `/wp-json/wp-sync/v1/courses?featured=1`, `/wp-json/wp-sync/v1/events?upcoming=1`

2. **Course Catalog (`/courses`)**
   - Entrypoint: `src/pages/CourseCatalog.tsx`
   - Consumes: `/wp-json/wp-sync/v1/courses`

3. **Course Detail (`/courses/:slug`)**
   - Entrypoint: `src/pages/CourseDetail.tsx`
   - Consumes: `/wp-json/wp-sync/v1/courses/:slug`

4. **Events (`/events`)**
   - Entrypoint: `src/pages/Events.tsx`
   - Consumes: `/wp-json/wp-sync/v1/events`

5. **Event Detail (`/events/:slug`)**
   - Entrypoint: `src/pages/EventDetail.tsx`
   - Consumes: `/wp-json/wp-sync/v1/events/:slug`

6. **About (`/about`)**
   - Entrypoint: `src/pages/About.tsx`
   - Consumes: *None (Static content only)*

7. **Contact (`/contact`)**
   - Entrypoint: `src/pages/Contact.tsx`
   - Consumes: *None (Static content only)*

## Components

- **CourseCard**
   - File: `src/components/CourseCard.tsx`
   - Reused by: `Home` and `CourseCatalog` page routes.
