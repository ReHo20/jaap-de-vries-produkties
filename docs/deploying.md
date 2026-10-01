# Deploying

There is no deploy pipeline. Deploy is `git pull` plus `composer install` on the server,
by hand. The server has no Node and builds nothing: the theme arrives already built,
inside the Composer package.

The server commands below are deliberately not Composer scripts. `composer install`
cannot sensibly be a script inside the `composer.json` it is installing, and `wp` is not
guaranteed to be on a given host's PATH. Wrapping them would produce an alias that works
here and fails there — worse than three lines you can read.

## Release checklist

**Locally**

```bash
composer lint     # check-platform-reqs, validate, PHPStan, PHP-CS-Fixer
git commit
git push
```

Theme changes are not part of this. They are made, linted and built in the
[Sightline](https://github.com/Giraffes4Zebras/sightline) repository, then pulled in
here — and `composer.lock` has to travel with them, or the server deploys the commit it
already had:

```bash
composer update g4z-theme/sightline
git add composer.lock
```

If a local symlink is standing in for the installed theme, remove it before you check
what a release actually contains. Otherwise you are looking at your working copy rather
than at the locked commit the server will get.

**On the server**

```bash
git pull
composer install --no-dev --optimize-autoloader
wp cache flush
```

`--no-dev` matters: PHPStan and the fixers have no business on a production host, and
`roave/security-advisories` will refuse to install against some production package sets.

## Verifying a release

- The page renders and the styles are current — check a `?ver=` value against
  `filemtime()` of the file it points at.
- `web/app/debug.log` has no new entries.
- A page containing an interactive block still hydrates (open it, click something).

## WP Rocket — optional, per client

WP Rocket is not required. A licence is bought per client, so requiring it would mean an
install fails, or ships a plugin nobody can activate, for a client without one.

Add it when this client has a licence:

```bash
composer require wp-media/wp-rocket:3.21.*
```

`bin/copy-advanced-cache.php` runs on every install and no-ops when the plugin is
absent, so nothing else has to change either way. `WP_ROCKET_EMAIL` and `WP_ROCKET_KEY`
are already in `.env.example`, empty.

Once installed, its settings have to be configured against this stack rather than left at
defaults. The table below is **not verified** — see the status note after it.

| Setting | Required state | Why |
| --- | --- | --- |
| Minify / combine JS | **Off** | Vite already minifies. Combining concatenates ES modules into one file, which breaks both the module graph and the import map. |
| Minify / combine CSS | **Off** | Same reasoning; the build already handles it. |
| Delay JavaScript execution | **On, with script modules excluded** | Delaying the interactivity runtime or a block's module breaks hydration — the markup renders and nothing responds. |
| Page cache | **On** | The reason the plugin is here. |
| Preload | **On** | — |
| Lazy load | **On** | Theme-rendered images already set `loading="lazy"`; Rocket's version covers CSS backgrounds and iframes. |

Exclusion patterns for *Delay JavaScript execution*:

```
/wp-includes/js/dist/script-modules/
/app/themes/sightline/dist/js/
```

The first covers WordPress's own interactivity runtime and router; the second covers
every per-block module the theme builds.

### `WP_CACHE` must be defined

When WP Rocket is installed, `composer install` copies `web/app/advanced-cache.php` into
place — but WordPress only loads it when `WP_CACHE` is true. Without that constant the
drop-in is dead code: no page cache, no lazy load, no delayed JavaScript — while the
settings screen reports all three as enabled.

With Rocket absent there is no drop-in to load and the constant does nothing, which is
why it stays defined either way rather than being made conditional on a plugin.

`config/application.php` defaults it to true, and `config/environments/development.php`
turns it off, because serving a cached page while editing the thing that produced it
wastes more time than the cache saves.

### Status: the settings table above is NOT yet verified

> The plan called for proving that Rocket leaves the import map and `type="module"` tags
> intact, and that an interactive block still hydrates with *Delay JavaScript execution*
> enabled. **That could not be verified**, because WP Rocket withholds its optimisation
> features without a valid licence and no `WP_ROCKET_KEY` exists in this environment.
>
> What was established: with the plugin active, `WP_CACHE` true, the settings above
> written directly to the options table and the cache flushed, WP Rocket produced no
> effect on the output at all — no cache marker, no lazy-load attributes, no delayed
> scripts — and `rocket_valid_key()` returns false.
>
> **Do this on the first client site that has a licence**, before trusting the table:
> load a page with an interactive block, confirm the import map and `type="module"` tags
> survive in view source, and confirm the block still responds to a click. Then correct
> this table in the base, so the next client inherits the answer rather than the
> question.
