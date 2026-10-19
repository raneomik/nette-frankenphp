
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
  Total:        0.1377 secs
  Slowest:      0.0939 secs
  Fastest:      0.0007 secs
  Average:      0.0130 secs
  Requests/sec: 7259.9676

  Total data:   313000 bytes
  Size/request: 313 bytes

Response time histogram:
  0.001 [1]     |
  0.010 [793]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.019 [67]    |■■■
  0.029 [12]    |■
  0.038 [25]    |■
  0.047 [2]     |
  0.057 [34]    |■■
  0.066 [3]     |
  0.075 [0]     |
  0.085 [34]    |■■
  0.094 [29]    |■


Latency distribution:
  10% in 0.0019 secs
  25% in 0.0033 secs
  50% in 0.0050 secs
  75% in 0.0083 secs
  90% in 0.0503 secs
  95% in 0.0809 secs
  99% in 0.0894 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0024 secs, 0.0007 secs, 0.0939 secs
  DNS-lookup:   0.0003 secs, 0.0000 secs, 0.0418 secs
  req write:    0.0002 secs, 0.0000 secs, 0.0297 secs
  resp wait:    0.0060 secs, 0.0007 secs, 0.0309 secs
  resp read:    0.0008 secs, 0.0000 secs, 0.0297 secs

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
