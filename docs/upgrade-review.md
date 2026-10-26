# Upgrade review: Harbor Tours booking site, PHP 7.4 to 8.3 (sample report)

*The site is fictional, written for this demo with breaks planted on purpose, of the kind found in PHP code written for PHP 5 and 7 and still running on an old server: a plain PHP booking form, pricing with gift vouchers and a passenger list for a boat tour company. Review by an AI agent (Claude), Nightly Works.*

**Scope**: `src/` and `public/`, for a move from PHP 7.4 (end of life since November 2022) to PHP 8.3. Reviewed in this repository; the site has no live deployment.
**Method**: a static scan (PHPCompatibility 10, `testVersion 8.3-`) and a source read; each break written as a test of what the site did on PHP 7.4 (`tests/UpgradeTest.php`, in PHP 7.4 syntax so it runs on both sides), checked to pass on PHP 7.4 with the code and dependencies as delivered and to fail on PHP 8.3 (`scripts/prove-breaks.sh`), then fixed one commit at a time until it passes on PHP 8.3 and 8.4.
**Result**: 5 breaks: 3 stop the page with a fatal error, 1 refuses every booking without a voucher with no error in the logs, 1 fills the logs with deprecations that PHP 9 will turn into errors. All fixed; the site behaves as it did on PHP 7.4.

## Summary for the owner

On PHP 8 the booking form would stop working: every request fails before the page loads, and once that is fixed, any booking with no voucher is refused as "Unknown voucher", silently, because PHP 8 compares an empty field differently. The skipper's passenger list fails too. The usual automated scan finds three of the five problems; the two that cost bookings (the empty children field and the empty voucher field) are invisible to it and only show up when the booking is actually run, which is what the tests do. All five are fixed with small changes, and the tests now guard each one.

## What the static scan finds, and what it misses

| # | Break | PHPCompatibility | Tests |
|---|---|---|---|
| 1 | `get_magic_quotes_gpc()` removed | found | fails |
| 2 | `each()` removed, `implode()` argument order removed | found | fails |
| 3 | an empty number field in arithmetic: `TypeError` | not found | fails |
| 4 | `"" == 0` is now false | not found | fails |
| 5 | deprecations: `"${var}"` / dynamic property / `null` to `strlen()` | found / not found / not found | fails |

The scan reads the code; breaks 3 and 4 depend on the values the form sends, which only running the code reveals.

## Breaks

### 1. Every request fails: `get_magic_quotes_gpc()` no longer exists (fatal)

**What**: `Input::clean()`, which every form goes through, calls `get_magic_quotes_gpc()`. It was deprecated in PHP 7.4 and removed in PHP 8.0.
**Impact**: "Call to undefined function" on every booking.
**Fix**: the call removed. Magic quotes were removed in PHP 5.4, so it had returned `false` ever since and the `stripslashes()` behind it never ran.
**Verified**: input is trimmed as before, apostrophes kept (test 1).

### 2. The passenger list fails: `each()` and the old `implode()` order (fatal)

**What**: `Manifest::fromLines()` loops with `while (list(, $line) = each($lines))` and calls `implode($fields, ';')`. `each()` was removed in PHP 8.0; passing the separator after the array was deprecated in 7.4 and removed in 8.0.
**Impact**: the skipper gets no passenger list.
**Fix**: `foreach`, and `implode(';', $fields)`.
**Verified**: the same list and seat total as on PHP 7.4, comments and blank lines skipped (test 2).

### 3. An empty children field stops the booking (fatal)

**What**: `Pricing::price()` multiplies the form's raw strings: `$booking->children * $tour['child_cents']`. On PHP 7.4 an empty field counted as 0, with a warning in the log; PHP 8 throws `TypeError: Unsupported operand types: string * int`.
**Impact**: any family that leaves "children" empty, which the form allows, gets an error page instead of a price.
**Fix**: party sizes are read as integers when the form is read (`Booking::fromForm()`), so an empty or missing field is 0, as before.
**Verified**: two adults, children left empty, voucher 4821: 63.00 € as on PHP 7.4 (test 3).

### 4. Every booking without a voucher is refused (silent)

**What**: "no voucher" is tested with `$booking->voucher == 0`. On PHP 7.4 an empty string equals 0; PHP 8 compares a non-numeric string with a number as strings, so `"" == 0` is false and the empty field is looked up as a voucher code.
**Impact**: "Unknown voucher" for most customers. No error or warning is logged: the code runs as written, only the comparison's result changed.
**Fix**: an explicit empty-string test, `trim((string) $booking->voucher) === ''`.
**Verified**: voucher left empty: full price, no error (test 4). Numeric voucher codes and unknown codes behave as before (normal-use test).

### 5. Deprecations of PHP 8.1 and 8.2 (warnings now, errors in PHP 9)

**What**: `strlen(null)` when the phone (or name) is not sent (8.1), `$booking->total` created on the fly without a declaration (8.2), and `"${var}"` in strings (8.2).
**Impact**: a deprecation line in the log for each booking, hiding real errors; each becomes an error in a future major version.
**Fix**: missing name and phone read as `''`, `$total` declared, `{$var}` in strings.
**Verified**: the whole booking flow raises no deprecation, and the confirmation text is unchanged (test 5).

## After the fixes

`composer.json` requires PHP ^8.3 and `composer compat` runs the static scan for PHP 8.3 and later: clean. CI runs the tests on PHP 8.3 and 8.4, the code as delivered on PHP 7.4 (every test passes) and on PHP 8.3 (each break reproduces).

## Checked next in a full upgrade

The rest of the site's pages and includes, the server's `php.ini` (error display off, log on, `memory_limit`), extensions the host must provide under PHP 8.3, the database driver (`mysql_*` code, if any, needs PDO or mysqli), third-party libraries pinned to PHP 7 versions, and PHPUnit 10 or later once the tests no longer need to run on PHP 7.4. A staging copy on PHP 8.3 with the real traffic's logs watched for a day before switching.
