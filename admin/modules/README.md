# Admin Modules Overview

This folder contains the main modules for the security and maintenance ecosystem:

- **sentinel.php**: Multi-layer security scanner (static scan, integrity monitor, supply chain audit, reporting)
- **patcher_backend.php**: Safe file read/write logic, backup, directory restriction
- **patcher_ui.php**: Browser-based code editor, file selector, diff preview, backend connection
- **automator.php**: Sitemap generator, triggered by patcher save and CMS news post events
- **trap.php**: Canary tokens (fake admin user/password, alert on access)

Each module is designed to work with the existing site structure and not break current functionality. See each file for implementation details.
