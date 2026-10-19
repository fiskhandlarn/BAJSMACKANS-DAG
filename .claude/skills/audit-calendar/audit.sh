#!/usr/bin/env bash
# Lists what in the calendar breaks the Links, Image and Emoji rules of
# .claude/skills/add-temadag/SKILL.md. Prints one tab-separated line per
# problem (kind, title, problem, url). Exits 1 when a link or image has a
# problem or a temadag's emoji is undecided (no emoji property; false is a
# decided "no emoji") or invalid, 2 when the calendar is unreadable.
# Usage, from the repo root:
# bash .claude/skills/audit-calendar/audit.sh [days.json]
set -u
file=${1:-days.json}
found=0
jq -e 'type == "array" and all(.[];
  (.title | type) == "string" and .title != ""
  and (.image | type) == "string" and .image != ""
  and (.links | type) == "array" and (.links | length) > 0
  and all(.links[]; type == "string" and . != ""))' "$file" >/dev/null || {
  echo "$file is not a JSON array of temadagar, each with a title, links and an image" >&2
  exit 2
}
# Wikimedia answers 429 to curl's default agent
ua='bajsmackans-dag-calendar-audit (https://github.com/fiskhandlarn/BAJSMACKANS-DAG)'
# --retry covers timeouts, 429 and 5xx
curl=(curl -s -A "$ua" --retry 2 --max-time 20)

# curl reports 000 when it got no response at all, such as a dead host
http() {
  [ "${1:-000}" = 000 ] && echo unreachable || echo "HTTP $1"
}

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
  case $code in 200 | 206) ;; *) http "$code" && return ;; esac
  case $type in image/*) ;; *) echo "type ${type:-none}" && return ;; esac
  case $size in '' | *[!0-9]*) echo "size unknown" && return ;; esac
  [ "$size" -le 1100000 ] || echo "size $size bytes"
}

# ponytail: sequential, about a minute for 160 urls; parallelise if the calendar grows
while IFS=$'\t' read -r kind title url; do
  case $kind in
    link)
      code=$("${curl[@]}" -o /dev/null -w '%{http_code}' "$url")
      [ "$code" = 200 ] || report link "$title" "$(http "$code")" "$url"
      ;;
    image)
      problem=$(image_problem "$url")
      [ -z "$problem" ] || report image "$title" "$problem" "$url"
      ;;
    # an emoji line carries its problem where the others carry a url
    emoji) report emoji "$title" "$url" "" ;;
  esac
done < <(jq -r '.[] | .title as $t
  | (.links[] | ["link", $t, .]), ["image", $t, .image],
    (if has("emoji") | not then ["emoji", $t, "emoji undecided"]
      elif .emoji == false
        or (.emoji | type == "string" and test("^:[a-z0-9_+-]+:$")) then empty
      else ["emoji", $t, "emoji invalid: \(.emoji | tojson)"] end)
  | @tsv' "$file")

exit $found
