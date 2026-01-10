![](https://heatbadger.now.sh/github/readme/contributte/demo-frankenphp/)

<p align=center>
  <a href="https://github.com/contributte/demo-frankenphp/actions"><img src="https://badgen.net/github/checks/contributte/demo-frankenphp/master"></a>
  <a href="https://coveralls.io/r/contributte/demo-frankenphp"><img src="https://badgen.net/coveralls/c/github/contributte/demo-frankenphp"></a>
  <a href="https://packagist.org/packages/contributte/demo-frankenphp"><img src="https://badgen.net/packagist/dm/contributte/demo-frankenphp"></a>
  <a href="https://packagist.org/packages/contributte/demo-frankenphp"><img src="https://badgen.net/packagist/v/contributte/demo-frankenphp"></a>
</p>
<p align=center>
  <a href="https://packagist.org/packages/contributte/demo-frankenphp"><img src="https://badgen.net/packagist/php/contributte/demo-frankenphp"></a>
  <a href="https://github.com/contributte/demo-frankenphp"><img src="https://badgen.net/github/license/contributte/demo-frankenphp"></a>
  <a href="https://bit.ly/ctteg"><img src="https://badgen.net/badge/support/gitter/cyan"></a>
  <a href="https://bit.ly/cttfo"><img src="https://badgen.net/badge/support/forum/yellow"></a>
  <a href="https://contributte.org/partners.html"><img src="https://badgen.net/badge/sponsor/donations/F96854"></a>
</p>

<p align=center>
Website 🚀 <a href="https://contributte.org">contributte.org</a> | Contact 👨🏻‍💻 <a href="https://f3l1x.io">f3l1x.io</a> | Twitter 🐦 <a href="https://twitter.com/contributte">@contributte</a>
</p>

----

![](.docs/phpinfo.png)

![](.docs/terminal.png)

-----

## Goal

Demo of [FrankenPHP](https://github.com/dunglas/frankenphp) with [Nette Framework](https://nette.org).

## Installation

You will need `PHP 8.2+` and [Composer](https://getcomposer.org/).

Create project using composer.

```bash
composer create-project -s dev contributte/demo-frankenphp acme
```

Now you have application installed. It's time to run it.

## Startup

Spin up Docker stack. FrankenPHP with Caddyserver.

```bash
docker compose up
```

Then visit [http://localhost:8080](http://localhost:8000) in your browser.

List of URL's:

- [http://localhost:8080/](http://localhost:8000)
- [http://localhost:8080/api](http://localhost:8000/api)
- [http://localhost:8080/api/phpinfo](http://localhost:8000/api/phpinfo)

## Benchmark (with [hey](https://github.com/rakyll/hey) - no debug, no xdebug)

### FrankenPHP
```
➜  bin/dev -f --port=443 -d -p // frankenphp on 443 to solve tls issues
➜ bin/hey -n 1000 -c 100 https://localhost

Summary:
  Total:        0.3178 secs
  Slowest:      0.1082 secs
  Fastest:      0.0009 secs
  Average:      0.0298 secs
  Requests/sec: 3146.8328

  Total data:   1049000 bytes
  Size/request: 1049 bytes

Response time histogram:
  0.001 [1]     |
  0.012 [84]    |■■■■■■■■■
  0.022 [345]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.033 [361]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.044 [104]   |■■■■■■■■■■■■
  0.055 [5]     |■
  0.065 [0]     |
  0.076 [0]     |
  0.087 [43]    |■■■■■
  0.097 [20]    |■■
  0.108 [37]    |■■■■


Latency distribution:
  10% in 0.0127 secs
  25% in 0.0184 secs
  50% in 0.0240 secs
  75% in 0.0309 secs
  90% in 0.0768 secs
  95% in 0.0877 secs
  99% in 0.1009 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0033 secs, 0.0009 secs, 0.1082 secs
  DNS-lookup:   0.0001 secs, 0.0000 secs, 0.0172 secs
  req write:    0.0000 secs, 0.0000 secs, 0.0058 secs
  resp wait:    0.0264 secs, 0.0009 secs, 0.0763 secs
  resp read:    0.0000 secs, 0.0000 secs, 0.0015 secs

Status code distribution:
  [200] 1000 responses

```

### Built-in PHP server
```
➜  bin/dev -d -p // default
➜  bin/hey -n 1000 -c 100 http://localhost:8080

Summary:
  Total:        0.9341 secs
  Slowest:      0.1191 secs
  Fastest:      0.0175 secs
  Average:      0.0880 secs
  Requests/sec: 1070.5212

  Total data:   1064000 bytes
  Size/request: 1064 bytes

Response time histogram:
  0.017 [1]     |
  0.028 [23]    |■
  0.038 [10]    |■
  0.048 [12]    |■
  0.058 [17]    |■
  0.068 [12]    |■
  0.078 [47]    |■■
  0.089 [32]    |■■
  0.099 [775]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.109 [61]    |■■■
  0.119 [10]    |■


Latency distribution:
  10% in 0.0750 secs
  25% in 0.0892 secs
  50% in 0.0918 secs
  75% in 0.0936 secs
  90% in 0.0982 secs
  95% in 0.0995 secs
  99% in 0.1115 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0004 secs, 0.0175 secs, 0.1191 secs
  DNS-lookup:   0.0002 secs, 0.0000 secs, 0.0197 secs
  req write:    0.0001 secs, 0.0000 secs, 0.0034 secs
  resp wait:    0.0874 secs, 0.0162 secs, 0.1023 secs
  resp read:    0.0001 secs, 0.0000 secs, 0.0053 secs

Status code distribution:
  [200] 1000 responses


```

## Development

See [how to contribute](https://contributte.org/contributing.html) to this package.

This package is currently maintaining by these authors.

<a href="https://github.com/f3l1x">
    <img width="80" height="80" src="https://avatars2.githubusercontent.com/u/538058?v=3&s=80">
</a>

-----

Consider to [support](https://contributte.org/partners.html) **contributte** development team. Also thank you for using this project.
