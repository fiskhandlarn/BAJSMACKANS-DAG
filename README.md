# BAJSMACKANS-DAG

<img src="fika.png" alt="fika" title="In Sweden we call this floorsucker" width="200" />

## Install

```bash
composer install
```

## Run

```bash
composer run app
```

Test date:

```bash
php index.php --date="january 10"
```

Output JSON payload:

```bash
php index.php --date="january 10" --debug
```

Paste the payload into Slack's [Block Kit Builder](https://app.slack.com/block-kit-builder/) to preview the message without posting it.

## Lint

### PHP

```bash
composer run lint:php
```

### JSON

```bash
composer run lint:json
```

## Test

```bash
composer run test
```

Every entry in the data file is tested automatically (today, tomorrow, required fields), plus a check for duplicate titles.
Date parsing (`resolveDay()` in `src/days.php`) has unit tests with fixed expected dates.

## Sources

- https://www.lyckasmedbakning.nu/bak-fikakalender-2016/
