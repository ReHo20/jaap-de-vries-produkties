# jaap-de-vries-produkties

A client site. Bedrock on WordPress 7.1 / PHP 8.1, Gutenberg with ACF Blocks V3, Timber 2
templating. The theme is [Sightline](https://github.com/Giraffes4Zebras/sightline),
installed by Composer from the G4Z satis registry.

This repository owns the Bedrock host — `config/`, `bin/`, mu-plugins and the Composer
manifest — and no theme source. A child theme is planned and will live here as tracked
code; Sightline itself never will.

## Relationship to the base

This started as a clone of
[`esther-tierie`](https://github.com/Giraffes4Zebras/esther-tierie), which in turn came
from [`base-wordpress-v2`](https://github.com/Giraffes4Zebras/base-wordpress-v2),
stripped to host Sightline. `packages/theme-kit`, `web/app/themes/base-theme`, the
PHPUnit suite, the Twig linter and the whole Node build were removed, because Sightline
brings its own and is linted and built in its own repository.

`bin/init-project.php` went with them. It renamed a theme directory this repository
owned, and Sightline is a Composer dependency with a fixed installer name — so there was
nothing left for it to rename. It was not a theme *generator*: scaffolding the planned
child theme is new work, not a revival of that script.

## Requirements

| Tool | Version | Notes |
| --- | --- | --- |
| PHP | **8.1** | 8.3 and 8.5 must also pass. Herd ships them; pick the version per site in Herd |
| Composer | 2.x | Herd bundles one at `~/.config/herd/bin/composer.bat` |
| MySQL | 8.x | Herd free excludes it — use [DBngin](https://dbngin.com/) |
| Herd | free | Supports Bedrock's `web/` document root natively, no custom Valet driver |

Node is not needed to run this site: Sightline ships built. `.nvmrc` pins 24 for the
child theme, when it lands.

Required PHP extensions: `curl`, `fileinfo`, `json`, `mbstring`, `zip` (declared in
`composer.json`), plus the wider set CI installs: `gd`, `intl`, `simplexml`, `dom`, `xml`,
`xmlreader`, `xmlwriter`, `iconv`, `ctype`, `zlib`.

Your PATH `php` may point at a different build. Check with `php -v` and call the 8.1
binary explicitly if needed (`~/.config/herd/bin/php81`).

`composer.json` pins the resolution platform to 8.1.0, so working on a newer local PHP
still produces a lock that installs on the server.

### Troubleshooting: "The zip extension and unzip/7z commands are both missing"

Composer is running on a PHP build without `ext-zip`, so it cannot extract downloaded
packages. Herd's PHP has it; a hand-installed PHP (e.g. `C:\tools\php85`) often does not.

```shell
php --ini                 # find the php.ini in use
php -m | grep -i zip      # confirm whether zip is loaded
```

Uncomment `extension=zip` (and `gd`, `intl`, `fileinfo`) in that `php.ini`, then re-run.
`composer check-platform-reqs` catches this up front — it fails naming the missing
extension instead of dying halfway through a download.

## One-time setup, per machine

ACF Pro is a licensed package and Composer needs HTTP-basic credentials to download it.
This is **separate** from the `ACF_PRO_LICENSE` value in `.env`, which only activates the
plugin at runtime. Configure it once, globally, so no repository ever contains a
credential:

```shell
composer config --global http-basic.connect.advancedcustomfields.com <G4Z_LICENCE_KEY> http://localhost
```

Never create an `auth.json` inside a project. `.gitignore` lists it as a backstop only.

If the satis registry starts requiring credentials, they go in the same global config —
and in CI's `COMPOSER_AUTH` secret alongside the ACF key, or `g4z-theme/sightline` will
fail to resolve there while working locally.

## Project setup

```shell
# 1  Serve the project. Herd detects Bedrock and uses web/ as the document root.
herd link jaap-de-vries-produkties

# 2  Set this site's PHP version to 8.1 in the Herd UI (or `herd php:8.1`).

# 3  Create the database in DBngin, then:
cp .env.example .env
#    Set DB_NAME / DB_USER / DB_PASSWORD, and ACF_PRO_LICENSE.
#    Keep WP_BASE on a .test hostname — see "Licence activations" below.

# 4  Install PHP dependencies. This is what fetches WordPress core, the plugins
#    and Sightline; a fresh clone has no web/wp/ at all until it runs.
composer install

# 5  Generate authentication keys and salts.
composer generate-salts >> .env

# 6  Run the WordPress installer at http://jaap-de-vries-produkties.test

# 7  Set pretty permalinks. WordPress defaults a fresh install to plain ?p= URLs, and
#    the search template never renders under those — /search/<term>/ does not route,
#    so a search falls through to the blog index and looks like a theme bug.
wp rewrite structure '/%postname%/'
```

Serving the site before step 4 fails with `Failed opening required
.../web/wp/wp-blog-header.php`. That is the missing install, not a broken clone.

`php` on your PATH must satisfy `^8.1`. Composer scripts spawn `php` themselves, so a
stale older PHP earlier in PATH makes `composer lint` fail with a platform error even
though the site runs fine under Herd.

## Working on the theme

Sightline is a dependency. Editing `web/app/themes/sightline/` from this repository does
nothing durable — the next `composer install` overwrites it, and CI and the server
resolve the package from satis regardless. Theme changes go through the Sightline
repository.

The constraint that makes this awkward is that **satis rebuilds hourly**, so a fix pushed
to Sightline is invisible here for up to an hour. While the site is being built, symlink
over the installed copy instead:

```shell
rm -rf web/app/themes/sightline
ln -s ~/Sites/sightline web/app/themes/sightline   # path to your Sightline clone
```

`composer install` and `composer update` replace the symlink with the real package. That
is not a failure; recreate it afterwards. The directory is gitignored either way, so
neither form shows up in `git status`.

Pulling theme work that has landed on `develop` is explicit, and the lock travels with it:

```shell
composer update g4z-theme/sightline
git add composer.lock
```

The lock is what makes a deploy safe: the server installs the locked commit, so nothing
shifts under a live site because someone pushed to `develop`.

### Before go-live

The branch requirement is a build-phase convenience. Once the theme settles, tag a
Sightline release, wait for the next hourly satis rebuild, then pin it:

```shell
composer require g4z-theme/sightline:^0.1
```

Then drop the local symlink, so the site runs the same package the server will.

## Quality gates

```shell
composer lint      # check-platform-reqs, validate, PHPStan, PHP-CS-Fixer
composer fix       # everything auto-fixable
```

Both cover `bin/` and `config/` only. Sightline's PHP, Twig and assets are linted in its
own repository — linting a Composer dependency from here would report findings nobody can
fix in place, and would go red on a version bump rather than on anything this repository
changed.

## Licence activations

ACF ignores development and staging sites when counting activations against the licence.
Detection is by hostname: `.test`, `.local`, `.loc`, `.localhost` TLDs and `stage.` /
`staging.` subdomains are free. **Any other local hostname counts as production and
consumes an activation**, so keep `WP_HOME` on `.test` locally.

## Deploying

`git pull` plus `composer install` on the server, by hand. There is no pipeline and the
server builds nothing. See [`docs/deploying.md`](docs/deploying.md) for the checklist and
the WP Rocket configuration.
