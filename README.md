
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

Then visit [https://localhost:8000](http://localhost:8000) in your browser.

## Benchmark (with [hey](https://github.com/rakyll/hey) in no-debug mode & without xdebug)

### FrankenPHP
```
➜  bin/dev -f --port=443 -d -p // frankenphp on 443 to solve tls issues
➜  bin/hey -n 1000 -c 100 https://localhost

Summary:
  Total:        0.3187 secs
  Slowest:      0.1038 secs
  Fastest:      0.0010 secs
  Average:      0.0292 secs
  Requests/sec: 3137.8056

  Total data:   316000 bytes
  Size/request: 316 bytes

Response time histogram:
  0.001 [1]     |
  0.011 [81]    |■■■■■■■■■
  0.022 [347]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.032 [360]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.042 [90]    |■■■■■■■■■■
  0.052 [19]    |■■
  0.063 [2]     |
  0.073 [0]     |
  0.083 [44]    |■■■■■
  0.094 [15]    |■■
  0.104 [41]    |■■■■■


Latency distribution:
  10% in 0.0125 secs
  25% in 0.0185 secs
  50% in 0.0230 secs
  75% in 0.0301 secs
  90% in 0.0781 secs
  95% in 0.0852 secs
  99% in 0.0985 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0040 secs, 0.0010 secs, 0.1038 secs
  DNS-lookup:   0.0012 secs, 0.0000 secs, 0.0358 secs
  req write:    0.0001 secs, 0.0000 secs, 0.0057 secs
  resp wait:    0.0250 secs, 0.0010 secs, 0.0824 secs
  resp read:    0.0000 secs, 0.0000 secs, 0.0040 secs

Status code distribution:
  [200] 1000 responses

```

compared to :

### Built-in PHP server
```
➜  bin/dev -d -p // default
➜  bin/hey -n 1000 -c 100 http://localhost:8080

Summary:
  Total:        0.7709 secs
  Slowest:      0.0955 secs
  Fastest:      0.0164 secs
  Average:      0.0723 secs
  Requests/sec: 1297.2564


Response time histogram:
  0.016 [1]     |
  0.024 [24]    |■
  0.032 [13]    |■
  0.040 [21]    |■
  0.048 [17]    |■
  0.056 [8]     |
  0.064 [17]    |■
  0.072 [73]    |■■■■
  0.080 [718]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.088 [104]   |■■■■■■
  0.095 [4]     |


Latency distribution:
  10% in 0.0637 secs
  25% in 0.0741 secs
  50% in 0.0758 secs
  75% in 0.0773 secs
  90% in 0.0803 secs
  95% in 0.0820 secs
  99% in 0.0825 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0003 secs, 0.0164 secs, 0.0955 secs
  DNS-lookup:   0.0001 secs, 0.0000 secs, 0.0173 secs
  req write:    0.0001 secs, 0.0000 secs, 0.0007 secs
  resp wait:    0.0718 secs, 0.0145 secs, 0.0826 secs
  resp read:    0.0001 secs, 0.0000 secs, 0.0059 secs

Status code distribution:
  [200] 1000 responses

```
