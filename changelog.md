# Changelog

## 1.0.0 - November 17, 2026
The first release.

Added
* `Backdrop\Resources\enqueue_style()`, `enqueue_script()`, `asset()` and `is_built()`, which load the files Vite builds by their source path, using `manifest.json`.
* Scripts also enqueue the stylesheets Vite split out of them, and can be printed as `type="module"`.
* Child theme builds are checked before the parent theme's.
* Vite dev server support with the `BACKDROP_VITE_DEV_SERVER` constant or the `backdrop/resources/dev_server` filter.
* `Backdrop\Resources\Provider`, which binds the shared resources as `resources` and shows a dashboard notice when the theme hasn't been built.
* Dependency free tests (`php tests/run.php`) and a GitHub Action that lints and runs them on PHP 7.4 to 8.3.
