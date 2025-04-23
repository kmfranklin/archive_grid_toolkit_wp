# Archive & Grid Toolkit

**Private plugin for Nomadic Software**

Provides three filterable grid shortcodes and AJAX-powered live filtering for blog posts and resources.

---

## Installation

1. Zip the `archive-grid-toolkit` folder and upload via **Plugins > Add New > Upload Plugin**.
2. Activate **Archive & Grid Toolkit**.

---

## Shortcodes

- **`[blog_grid]`**
  Shows blog posts only, with **Search**, **Industry**, and **Service** filters.

- **`[topic_archive_grid]`**
  For tag or category archives; shows posts and resources with **Search**, **Industry**, **Service**, and **Resource Type** filters.

- **`[resource_grid]`**
  Standalone resources page (all resource CPTs except `blog`), with **Search**, **Industry**, **Service**, and **Resource Type** filters.

- **`[topic_description]`**
  Outputs the current tag/category description (for child categories of `industry` or `service`).

---

## Usage

1. Add `[blog_grid]` to the main Blog page.
2. In Themer archive layout, under the H1/title, drop:

```
[topic_description]
[topic_archive_grid]
```

3. On standalone Resources page, use `[resource_grid]`.

---

© 2025 Nomadic Software — All Rights Reserved.  
This code is proprietary and may not be copied or redistributed without permission.
