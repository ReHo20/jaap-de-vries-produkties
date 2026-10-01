# jaap-de-vries-produkties

A client site. Bedrock host, Gutenberg with ACF Blocks V3, Timber 2 templating. The
theme is [Sightline](https://github.com/Giraffes4Zebras/sightline), installed by Composer
from the G4Z satis registry.

## Stack

| | |
| --- | --- |
| WordPress | 7.1 |
| PHP | 8.1 (CI also runs 8.3 and 8.5) |
| Templating | Timber 2.3 / Twig 3.27 |
| Fields & blocks | ACF Pro 6.8, Blocks V3 |
| Theme | `g4z-theme/sightline`, pinned to `dev-develop` until it tags |
| Local | Herd + DBngin |

## Where things live

This repository owns Bedrock and nothing else: `config/`, `bin/`, `web/app/mu-plugins/`,
and the Composer manifest. There is no theme source here, no `packages/`, and no
JavaScript project.

```
web/app/themes/sightline/    Composer-managed — gitignored, never edited here
```

**Changes to the theme go through the Sightline repository**, not this one. The next
`composer install` overwrites that directory, so an edit made here is lost silently and
is invisible to CI and the server, which resolve the package from satis.

Locally the directory is often a symlink to a Sightline working copy, so that fixes do
not wait on the registry's hourly rebuild. `composer install` and `composer update`
replace the symlink with the real package; recreate it afterwards.

Pulling theme work that has landed on `develop` is explicit, and the lock travels with it:

```bash
composer update g4z-theme/sightline
git add composer.lock
```

## A child theme is planned

It will live in `web/app/themes/` as this repository's own code — tracked, unlike
Sightline. When it lands it needs its own npm block in `.github/dependabot.yml`, and the
asset and lint steps CI currently has no reason to run.

## Pinned APIs

Timber 2 is thin in training data and 1.x APIs get hallucinated confidently. Use these:

```
ALLOWED    Timber::context()   Timber::get_post()   Timber::get_posts()
           Timber::render()    Timber::compile()    Timber::get_menu()
           post.excerpt({ words: 24 })   post.thumbnail_id   post.link
           posts.pagination
           add_filter('timber/context', …)   add_filter('timber/locations', …)

FORBIDDEN  new Timber\Post(…)      Timber::get_context()      Timber::$dirname
           post.preview            post.get_field(…)          TimberPost / TimberMenu
           Timber::get_pagination(…)
```

`post.preview` is the one that bites — it renders fine and logs a deprecation on every
item. `Timber::get_pagination()` is the same trap on a listing template: it renders, it
reads the global query rather than the collection in front of it, and it deprecates on
every request. `posts.pagination` is the 2.x form.

```
BLOCKS     apiVersion 3 AND acf.blockVersion 3, always
           viewStyle   -> front end only          <- use this
           style       -> front end AND editor    <- not this
           the template must emit block.className, or block style variations do nothing

IAPI       import { store, getContext, getElement } from '@wordpress/interactivity'
           directives are DOUBLE dash: data-wp-on--click, data-wp-bind--hidden
           data-wp-interactive must ALSO be on the element carrying
           data-wp-router-region, or navigation deletes the region

           server-side directive processing runs for BLOCKS ONLY (supports.interactivity).
           A plain component with a store writes its initial ARIA state literally AND
           carries the directive, and hides things in CSS — data-wp-bind--hidden is not
           there at first paint.
```

## Twig traps

- A comment `{# … #}` **inside** a hash literal or expression is a parse error, not a
  comment. Put it on the line above.
- Includes pass explicit data and nothing else:
  `{% include 'components/card/card.twig' with { title: … } only %}`

## Commands

```bash
composer lint      # check-platform-reqs, validate, PHPStan, PHP-CS-Fixer
composer fix       # everything auto-fixable
```

`php` on your PATH must satisfy `^8.1` — Composer scripts spawn it. `composer.json`
pins the resolution platform to 8.1.0, so a newer local PHP still produces a lock the
server can install.

Both gates cover `bin/` and `config/` only. Sightline's PHP, Twig and assets are linted
and built in its own repository, deliberately: linting a Composer dependency from here
would report findings nobody can fix in place and would go red on a version bump rather
than on anything this repo changed.

`bin/` holds two scripts, both theme-agnostic: `generate-salts.php` and
`copy-advanced-cache.php`. The base's `init-project.php` and `compile-twig.php` were
deleted with the theme they served.

## Agent skills

### Issue tracker

GitHub Issues on this repository, via the `gh` CLI. Theme issues belong on
`Giraffes4Zebras/sightline`. See `docs/agents/issue-tracker.md`.

### Triage labels

The five canonical roles, each label string equal to its name. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context — `CONTEXT.md` and `docs/adr/` at the repo root, both created lazily. See `docs/agents/domain.md`.
