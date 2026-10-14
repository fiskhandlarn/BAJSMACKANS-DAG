# BAJSMACKANS-DAG

Announces Swedish temadagar (pastry days, Halloween and the like) in Slack, on the day itself and the day before.

## Language

### Calendar

**Calendar**:
The set of all temadagar the app knows about.
_Avoid_: Data file, days list, fikakalender

**Source**:
An external list of temadagar that the calendar draws from, such as a published baking calendar. The calendar has many sources.
_Avoid_: Fikakalender

**Temadag**:
A recurring themed day, such as Mazarinens dag or Halloween, with a title, a link and an image.
_Avoid_: Day, entry, fikadag, theme day

**Day rule**:
The text that says which date a temadag falls on each year, either fixed ("january 10") or movable ("first sunday of october", "47 days before easter").
_Avoid_: Day

**Date**:
One calendar date in a specific year, such as 2026-10-14.
_Avoid_: Day

### Posting

**Daily run**:
One scheduled check of the calendar against today's and tomorrow's dates, posting any announcements and reminders. A preview that never posts is not a daily run.
_Avoid_: Job, cron

**Dropped run**:
A daily run that the scheduler never started, so nothing is posted that day.
_Avoid_: Missed run, skipped run

**Announcement**:
The Slack message posted on a temadag's date, saying the temadag is today. Temadagar on the same date each get their own.
_Avoid_: Post, card, payload

**Reminder**:
The Slack message posted the date before a temadag, saying the temadag is tomorrow. Temadagar on the same date each get their own.
_Avoid_: Post, card, payload, heads-up
