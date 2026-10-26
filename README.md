# PHP 7.4 to 8.3 upgrade review (demo)

A proof piece for PHP upgrade jobs. A fictional booking site written for this demo in plain PHP 7.4 (a booking form, pricing with gift vouchers, a passenger list) with five breaks that appear on PHP 8, each written as a test that passes on PHP 7.4 with the code as delivered, fails on PHP 8.3, and passes again after its fix.

| # | Break on PHP 8 | Kind | Seen by the static scan |
|---|---|---|---|
| 1 | `get_magic_quotes_gpc()` removed: every request fails | fatal | yes |
| 2 | `each()` and the old `implode()` order removed: the passenger list fails | fatal | yes |
| 3 | an empty children field in arithmetic: `TypeError` | fatal | no |
| 4 | `"" == 0` is now false: every booking without a voucher is refused | silent | no |
| 5 | PHP 8.1 and 8.2 deprecations (`null` to `strlen()`, dynamic property, `"${var}"`) | log noise, slated to become errors in PHP 9 | in part |

The point of the tests: PHPCompatibility, the usual scan, finds the removed functions, but not the two breaks that cost bookings.

Full write-up, in the format a client would receive: [docs/upgrade-review.md](docs/upgrade-review.md).

## History

1. `as-delivered` (tag): the site as written for the demo, PHP 7.4.
2. The five breaks as tests, in PHP 7.4 syntax so they run on both versions.
3. One commit per fix, each turning one test green on PHP 8.3.
4. PHP ^8.3 required, the static scan added to CI.

## Run

PHP 8.3 and Composer:

```
composer install
vendor/bin/phpunit             # fixed code: every test passes
./scripts/prove-breaks.sh      # code as delivered on this PHP: each break reproduces
composer compat                # static scan for PHP 8.3 and later
```

CI also runs the code as delivered on PHP 7.4, where every test passes.

[![checks](https://github.com/nightly-works/php-upgrade-review-demo/actions/workflows/checks.yml/badge.svg)](https://github.com/nightly-works/php-upgrade-review-demo/actions/workflows/checks.yml)

Built by an AI agent (Claude) at [Nightly Works](https://github.com/nightly-works). MIT licence.
