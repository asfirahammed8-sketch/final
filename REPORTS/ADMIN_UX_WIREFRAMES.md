# WordPress Admin UI/UX Rebuild Wireframes

Production-grade layout wireframe mockups and descriptions replacing traditional WordPress option forms with an immersive React-style responsive cockpit.

---

## 1. Split-Section Cockpit View (1200px Grid)

```
+---------------------------------------------------------------------------------------------------------+
|  WP Sync Cockpit — Admin settings                                                [Reset System Default] |
+---------------------------------------------------------------------------------------------------------+
|                                                                                                         |
|  [ Sidebar Settings Nav ]        [ Centralized Config Panel ]                                           |
|  * Home Page Settings            +-------------------------------------------------------------------+  |
|  * Course Catalog Settings       | Hero Section Configuration                                        |  |
|  * Event Calendar Settings       |                                                                   |  |
|                                  | Hero Title (max 60 chars)                                         |  |
|                                  | [ Welcome to our Learning Hub                         ] (34/60)   |  |
|                                  | ✔ Live Validation: Valid heading syntax.                          |  |
|                                  |                                                                   |  |
|                                  | Hero Subtitle (max 150 chars)                                     |  |
|                                  | [ Expand your horizons with our hand-crafted courses & events     ]  |  |
|                                  |                                                                   |  |
|                                  | Hero CTA Button Text                                              |  |
|                                  | [ Get Started Now                                     ]           |  |
|                                  +-------------------------------------------------------------------+  |
|                                  | Counter Stats                                                     |  |
|                                  | Total Students: [ 15,000+  ]      Active Courses: [ 120+   ]     |  |
|                                  +-------------------------------------------------------------------+  |
|                                                                                                         |
+---------------------------------------------------------------------------------------------------------+
|  [✔ Settings autosaved in background]                                                [Manual Save Options]  |
+---------------------------------------------------------------------------------------------------------+
```

---

## 2. Key Production UX Requirements

### A. Sticky Save & Operations Bar
* **Behavior:** A floating fixed footer pinned to the viewport bottom.
* **States:**
  - **Clean:** Displaying "All changes saved to database".
  - **Dirty:** Highlighted in blue, displaying "You have unsaved options" next to a button `[Save Now]`.
  - **Loading:** Spinner icon on the action button with cursor input disabled.

### B. Skeleton Preloading Indicator
* **Visuals:** Pulsing grey placeholders instead of jerky loaders while calling headless APIs:
```
  +-----------------------+
  |  ########### (Title)  |
  |  ===================  |
  |  ===================  |
  +-----------------------+
```

### C. Live Inline Field Validation
* **Logic:** Front-end checks that trigger immediately on value changes.
  - **Prices:** Must be numbers only. Invalid strings turn field borders red with an text label: `❌ Original price must be a numeric value`.
  - **PDF URLs:** Must start with a valid `http://` or `https://` protocol.

### D. Keyboard Accessibility Shortcuts
* `Ctrl + S` or `Cmd + S` intercepts default browser saving and executes the AJAX save action asynchronously.
* `Tab` and `Shift + Tab` cycles strictly through admin fields with a custom 2px focus border.

### E. Drag-and-Drop Reordering (Courses & Events)
* Uses **SortableJS** / **HTML5 Drag & Drop** allowing administrators to drag courses visually to change their order:
```
  [::] Course 1: Mastering React & TypeScript        [Edit]
  [::] Course 2: Advanced WordPress Plugin Architecture [Edit]
```
Changing order immediately triggers a quick background request `/wp-json/wp-sync/v1/reorder` to update custom menu order priorities.
