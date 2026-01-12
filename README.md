
## Goal

Demo of [FrankenPHP](https://github.com/dunglas/frankenphp) with [Nette Framework](https://nette.org).

## Installation

You will need docker, docker compose, `PHP 8.2+` and [Composer](https://getcomposer.org/).

Create project using composer.

```bash
git clone https://github.com/raneomik/nette-frankenphp --branch plain-nette frankenette
```

Now you have application installed. It's time to run it.

## Startup

Spin up Docker stack. FrankenPHP with Caddyserver.


```bash
docker compose up -f compose.yml -f compose.dev.yml
```
or

```bash
bin/dev --franken // bin/dev -h to show available options
```

Then visit [https://localhost:8000](https://localhost:8000) in your browser.

## Benchmark (with [hey](https://github.com/rakyll/hey) in no-debug mode & without xdebug)

### FrankenPHP
```
➜  bin/dev -f --port=443 -d -p // frankenphp on 443 to solve tls issues (windows' wsl)
➜  bin/hey -n 1000 -c 100 https://localhost

Summary:
  Total:        0.2906 secs
  Slowest:      0.0770 secs
  Fastest:      0.0017 secs
  Average:      0.0261 secs
  Requests/sec: 3440.9203

  Total data:   313000 bytes
  Size/request: 313 bytes

Response time histogram:
  0.002 [1]     |
  0.009 [70]    |■■■■■■■■■■■
  0.017 [187]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.024 [246]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.032 [256]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.039 [131]   |■■■■■■■■■■■■■■■■■■■■
  0.047 [36]    |■■■■■■
  0.054 [13]    |■■
  0.062 [11]    |■■
  0.069 [18]    |■■■
  0.077 [31]    |■■■■■


Latency distribution:
  10% in 0.0105 secs
  25% in 0.0166 secs
  50% in 0.0242 secs
  75% in 0.0316 secs
  90% in 0.0407 secs
  95% in 0.0618 secs
  99% in 0.0764 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0015 secs, 0.0017 secs, 0.0770 secs
  DNS-lookup:   0.0001 secs, 0.0000 secs, 0.0136 secs
  req write:    0.0001 secs, 0.0000 secs, 0.0092 secs
  resp wait:    0.0237 secs, 0.0015 secs, 0.0553 secs
  resp read:    0.0000 secs, 0.0000 secs, 0.0009 secs

Status code distribution:
  [200] 1000 responses

```

compared to :

### Built-in PHP server
```
➜  bin/dev -d -p // default
➜  bin/hey -n 1000 -c 100 http://localhost:8080

Summary:
  Total:        0.8699 secs
  Slowest:      0.1128 secs
  Fastest:      0.0206 secs
  Average:      0.0816 secs
  Requests/sec: 1149.5049

  Total data:   307000 bytes // dev note: gziped
  Size/request: 307 bytes

Response time histogram:
  0.021 [1]     |
  0.030 [32]    |■■■
  0.039 [22]    |■■
  0.048 [16]    |■
  0.057 [8]     |■
  0.067 [31]    |■■■
  0.076 [11]    |■
  0.085 [387]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.094 [473]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.104 [8]     |■
  0.113 [11]    |■


Latency distribution:
  10% in 0.0662 secs
  25% in 0.0840 secs
  50% in 0.0851 secs
  75% in 0.0869 secs
  90% in 0.0921 secs
  95% in 0.0930 secs
  99% in 0.1045 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0008 secs, 0.0206 secs, 0.1128 secs
  DNS-lookup:   0.0006 secs, 0.0000 secs, 0.0222 secs
  req write:    0.0000 secs, 0.0000 secs, 0.0006 secs
  resp wait:    0.0807 secs, 0.0200 secs, 0.0943 secs
  resp read:    0.0000 secs, 0.0000 secs, 0.0005 secs

Status code distribution:
  [200] 1000 responses

```
