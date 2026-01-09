
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

### Adapterman
```
➜  bibin/dev --adapterman -p -d
➜  bin/hey -n 1000 -c 100 http://localhost:8000

Summary:
  Total:        0.5561 secs
  Slowest:      0.3559 secs
  Fastest:      0.0006 secs
  Average:      0.0355 secs
  Requests/sec: 1798.2557

  Total data:   205000 bytes
  Size/request: 205 bytes

Response time histogram:
  0.001 [1]     |
  0.036 [723]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.072 [150]   |■■■■■■■■
  0.107 [34]    |■■
  0.143 [29]    |■■
  0.178 [54]    |■■■
  0.214 [5]     |
  0.249 [1]     |
  0.285 [2]     |
  0.320 [0]     |
  0.356 [1]     |


Latency distribution:
  10% in 0.0045 secs
  25% in 0.0100 secs
  50% in 0.0197 secs
  75% in 0.0415 secs
  90% in 0.0987 secs
  95% in 0.1540 secs
  99% in 0.1709 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0006 secs, 0.0006 secs, 0.3559 secs
  DNS-lookup:   0.0005 secs, 0.0000 secs, 0.0209 secs
  req write:    0.0000 secs, 0.0000 secs, 0.0183 secs
  resp wait:    0.0346 secs, 0.0006 secs, 0.3375 secs
  resp read:    0.0001 secs, 0.0000 secs, 0.0109 secs

Status code distribution:
  [200] 1000 responses

```

compared to :

### FrankenPHP
```
➜  bin/dev -f --port=443 -d -p // frankenphp on 443 to solve tls issues
➜  bin/hey -n 1000 -c 100 https://localhost

Summary:
  Total:        0.3111 secs
  Slowest:      0.0866 secs
  Fastest:      0.0015 secs
  Average:      0.0285 secs
  Requests/sec: 3214.7256

  Total data:   316000 bytes
  Size/request: 316 bytes

Response time histogram:
  0.002 [1]     |
  0.010 [39]    |■■■■■
  0.019 [187]   |■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.027 [290]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.036 [270]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.044 [96]    |■■■■■■■■■■■■■
  0.053 [54]    |■■■■■■■
  0.061 [34]    |■■■■■
  0.070 [13]    |■■
  0.078 [9]     |■
  0.087 [7]     |■


Latency distribution:
  10% in 0.0149 secs
  25% in 0.0196 secs
  50% in 0.0266 secs
  75% in 0.0334 secs
  90% in 0.0463 secs
  95% in 0.0557 secs
  99% in 0.0735 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0006 secs, 0.0015 secs, 0.0866 secs
  DNS-lookup:   0.0002 secs, 0.0000 secs, 0.0264 secs
  req write:    0.0001 secs, 0.0000 secs, 0.0208 secs
  resp wait:    0.0259 secs, 0.0015 secs, 0.0619 secs
  resp read:    0.0002 secs, 0.0000 secs, 0.0117 secs

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
