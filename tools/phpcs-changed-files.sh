#!/usr/bin/env bash
set -euo pipefail

if [ "$#" -lt 2 ]; then
    echo "Usage: $0 <base-ref> <head-ref> [pathspec ...]" >&2
    exit 1
fi

base_ref=$1
head_ref=$2
shift 2

if [ "$#" -eq 0 ]; then
    set -- src
fi

mapfile -t changed_files < <(
    git diff --name-only --diff-filter=ACMR "$base_ref" "$head_ref" -- "$@" \
        | grep -E '\\.php$' || true
)

if [ "${#changed_files[@]}" -eq 0 ]; then
    echo "No changed PHP files to lint."
    exit 0
fi

printf '%s\0' "${changed_files[@]}" | xargs -0 vendor/bin/phpcs --standard=phpcs.xml
