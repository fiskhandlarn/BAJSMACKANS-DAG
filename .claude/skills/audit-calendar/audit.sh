#!/usr/bin/env bash
# Lists what in the calendar breaks the Links, Image and Emoji rules of
# .claude/skills/add-temadag/SKILL.md. Prints one tab-separated line per
# problem (kind, title, problem, url) and exits 1 when any exist, 2 when the
# calendar is unreadable.
# Usage, from the repo root:
# bash .claude/skills/audit-calendar/audit.sh [days.json]
set -u
file=${1:-days.json}
found=0
jq -e 'type == "array" and all(.[];
  (.title | type) == "string" and (.image | type) == "string"
  and (.links | type) == "array" and all(.links[]; type == "string"))' "$file" >/dev/null || {
  echo "$file is not a JSON array of temadagar, each with a title, links and an image" >&2
  exit 2
}
# Wikimedia answers 429 to curl's default agent
ua='bajsmackans-dag-calendar-audit (https://github.com/fiskhandlarn/BAJSMACKANS-DAG)'
# --retry covers timeouts, 429 and 5xx
curl=(curl -s -A "$ua" --retry 2 --max-time 20)

report() {
  printf '%s\t%s\t%s\t%s\n' "$@"
  found=1
}

# a one-byte range request, so a server that answers HEAD badly still shows
# status, type and size; one that ignores the range sends the whole image,
# and its downloaded size stands in for missing length headers. -w reads the
# final response only, past any attempts --retry made before it
image_problem() {
  local code type range length downloaded size
  IFS='|' read -r code type range length downloaded < <("${curl[@]}" -r 0-0 -o /dev/null \
    -w '%{http_code}|%{content_type}|%header{content-range}|%header{content-length}|%{size_download}\n' "$1")
  size=${range##*/}
  [ -n "$size" ] || size=$length
  [ -n "$size" ] || [ "$code" != 200 ] || size=$downloaded
  case $code in 200 | 206) ;; *) echo "HTTP ${code:-000}" && return ;; esac
  case $type in image/*) ;; *) echo "type ${type:-none}" && return ;; esac
  case $size in '' | *[!0-9]*) echo "size unknown" && return ;; esac
  [ "$size" -le 1100000 ] || echo "size $size bytes"
}

# ponytail: sequential, about a minute for 160 urls; parallelise if the calendar grows
while IFS=$'\t' read -r kind title url; do
  if [ "$kind" = link ]; then
    code=$("${curl[@]}" -o /dev/null -w '%{http_code}' "$url")
    [ "$code" = 200 ] || report link "$title" "HTTP $code" "$url"
  else
    problem=$(image_problem "$url")
    [ -z "$problem" ] || report image "$title" "$problem" "$url"
  fi
done < <(jq -r '.[] | .title as $t | (.links[] | ["link", $t, .]), ["image", $t, .image] | @tsv' "$file")

while IFS= read -r title; do
  report title "$title" "no trailing emoji" ""
done < <(jq -r '.[].title | select(test(":[a-z0-9_+-]+:$") | not)' "$file")

exit $found
