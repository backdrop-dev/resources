# Backdrop Resources

Loads the styles and scripts that [Vite](https://vite.dev) builds, for ClassicPress and WordPress themes and plugins built on [Backdrop](https://github.com/backdrop-dev/framework).

Vite gives each built file a hashed name, such as `css/screen-BtX2a9.css`, and lists them in `manifest.json`. This package reads the manifest so you can enqueue a file by its source path, such as `resources/scss/screen.scss`.

## Requirements
* PHP 7.4 or later
* [backdrop-dev/framework](https://github.com/backdrop-dev/framework) ^2.0, for the optional service provider

## Installation
```
composer require backdrop-dev/resources
```

The helper functions load with Composer's autoloader. Register the provider to also get a dashboard notice when the theme hasn't been built:

```php
$app->provider( \Backdrop\Resources\Provider::class );
```

## Usage
```php
use function Backdrop\Resources\enqueue_style;
use function Backdrop\Resources\enqueue_script;

add_action( 'wp_enqueue_scripts', function() {

	enqueue_style( 'my-theme-screen', 'resources/scss/screen.scss' );

	enqueue_script( 'my-theme-app', 'resources/js/app.js', [ 'jquery' ] );
} );
```

* `enqueue_style( $handle, $source, $deps = [], $media = 'all' )` enqueues a built stylesheet.
* `enqueue_script( $handle, $source, $deps = [], $args = [] )` enqueues a built script in the footer, and the stylesheets Vite split out of it (as `{handle}-1`, `{handle}-2` and so on). Pass `[ 'module' => true ]` for a script that imports other files, so it prints as `type="module"`; pass `[ 'in_footer' => false ]` to load it in the head.
* `asset( $source )` returns the built file's URL, for example to pass to `add_editor_style()`.
* `is_built()` tells you whether there is a build.

They return `false` (or an empty string) when the source file isn't in the build, so nothing is enqueued with a broken URL.

### Where builds are found
In the active theme's `public/assets` folder, with `public/assets/manifest.json`. With a child theme, the child theme's build is checked first, then the parent's, so a child theme can rebuild a file. Change the folder with the `backdrop/resources/folder` filter.

### Vite dev server
To load files from `vite` (the dev server) while you work, with instant reloads, set its URL in `wp-config.php`:

```php
define( 'BACKDROP_VITE_DEV_SERVER', 'http://localhost:5173' );
```

or with the `backdrop/resources/dev_server` filter. Files then load from the dev server, and Vite's client script is added. Remove it again for the live site.

## Vite configuration
Composer can't ship a Vite config, so add this `vite.config.js` to your theme. List every file you enqueue under `input`:

```js
import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  base: './',                  // asset URLs in the CSS are relative to the CSS file
  publicDir: false,
  build: {
    outDir: 'public/assets',
    assetsDir: '',
    manifest: 'manifest.json', // public/assets/manifest.json
    emptyOutDir: true,
    rollupOptions: {
      input: {
        screen: path.resolve(__dirname, 'resources/scss/screen.scss'),
        app: path.resolve(__dirname, 'resources/js/app.js'),
      },
      output: {
        entryFileNames: 'js/[name]-[hash].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: ({ name }) =>
          name && name.endsWith('.css')
            ? 'css/[name]-[hash][extname]'
            : 'media/[name]-[hash][extname]',
      },
    },
  },
  server: {
    cors: true,                // the site loads files from the dev server
  },
});
```

And in `package.json`:

```json
"scripts": {
  "dev": "vite",
  "build": "vite build",
  "watch": "vite build --watch"
},
"devDependencies": {
  "sass": "^1.93.3",
  "vite": "^7.3.7"
}
```

`public/assets` is built output: add it to `.gitignore` and build it in your release workflow.

## Tests
```
php tests/run.php
```

## Copyright and License
This project is licensed under the GNU GPL, version 2 or later.

2026 © Benjamin Lu
