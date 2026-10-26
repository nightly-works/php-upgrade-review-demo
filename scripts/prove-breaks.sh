#!/usr/bin/env bash
# Runs the upgrade tests on the current PHP (8.x) against the code as delivered (tag as-delivered) and
# checks that each of the five breaks' tests fails there, i.e. that the site as delivered breaks on PHP 8.
# Needs a clean tree under the paths below, a full clone (the tag) and `composer install`; the tree is
# put back to HEAD on exit.
set -euo pipefail
cd "$(dirname "$0")/.."

PATHS=(src public)
git diff --quiet HEAD -- "${PATHS[@]}" || { echo "uncommitted changes under ${PATHS[*]}: commit or stash first"; exit 1; }
restore() {
  git checkout -q HEAD -- "${PATHS[@]}"
  rm -f junit-as-delivered.xml
}
trap restore EXIT

git checkout -q as-delivered -- "${PATHS[@]}"

vendor/bin/phpunit --log-junit junit-as-delivered.xml >/dev/null 2>&1 || true

# One line per test: its name and whether it failed (a <failure> or <error> child).
results=$(php -r '
  $xml = simplexml_load_file($argv[1]);
  foreach ($xml->xpath("//testcase") as $case) {
      $failed = count($case->failure) + count($case->error) > 0 ? "failed" : "passed";
      echo $case["name"], " ", $failed, PHP_EOL;
  }' junit-as-delivered.xml)

echo "PHP $(php -r 'echo PHP_VERSION;'), code as delivered:"
status=0
for t in test_1_ test_2_ test_3_ test_4_ test_5_; do
  line=$(grep "^${t}" <<<"$results" || true)
  case "$line" in
    *" failed") echo "  breaks: ${line% failed}" ;;
    *" passed") echo "  test passed (break did not reproduce): ${line% passed}"; status=1 ;;
    *) echo "  missing: ${t}"; status=1 ;;
  esac
done
exit $status
