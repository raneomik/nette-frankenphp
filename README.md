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

## Benchmark (with [hey](https://github.com/rakyll/hey))

### FrankenPHP (no-debug mode)
```
➜  bin/dev -f --port=443 -d -p // frankenphp on 443 to solve tls issues
➜  bin/hey -n 1000 -c 100 https://localhost

Summary:
  Total:        0.6730 secs
  Slowest:      0.1338 secs
  Fastest:      0.0080 secs
  Average:      0.0631 secs
  Requests/sec: 1485.8235
  
  Total data:   306000 bytes
  Size/request: 306 bytes

Response time histogram:
  0.008 [1]     |
  0.021 [1]     |
  0.033 [28]    |■■■
  0.046 [50]    |■■■■■
  0.058 [413]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.071 [283]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.083 [54]    |■■■■■
  0.096 [132]   |■■■■■■■■■■■■■
  0.109 [22]    |■■
  0.121 [11]    |■
  0.134 [5]     |


Latency distribution:
  10% in 0.0479 secs
  25% in 0.0535 secs
  50% in 0.0586 secs
  75% in 0.0682 secs
  90% in 0.0907 secs
  95% in 0.0938 secs
  99% in 0.1124 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0013 secs, 0.0080 secs, 0.1338 secs
  DNS-lookup:   0.0006 secs, 0.0000 secs, 0.0412 secs
  req write:    0.0003 secs, 0.0000 secs, 0.0246 secs
  resp wait:    0.0588 secs, 0.0078 secs, 0.1233 secs
  resp read:    0.0003 secs, 0.0000 secs, 0.0148 secs

Status code distribution:
  [200] 1000 responses


```

### Built-in PHP server (no-debug mode)
```
➜  bin/dev -d -p // default
➜  bin/hey -n 1000 -c 100 http://localhost:8080
Summary:
  Total:        0.8311 secs
  Slowest:      0.1043 secs
  Fastest:      0.0137 secs
  Average:      0.0787 secs
  Requests/sec: 1203.1921
  

Response time histogram:
  0.014 [1]     |
  0.023 [19]    |■
  0.032 [11]    |■
  0.041 [11]    |■
  0.050 [19]    |■
  0.059 [20]    |■■
  0.068 [14]    |■
  0.077 [206]   |■■■■■■■■■■■■■■■■
  0.086 [507]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.095 [146]   |■■■■■■■■■■■■
  0.104 [46]    |■■■■


Latency distribution:
  10% in 0.0688 secs
  25% in 0.0767 secs
  50% in 0.0813 secs
  75% in 0.0853 secs
  90% in 0.0918 secs
  95% in 0.0945 secs
  99% in 0.1039 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0004 secs, 0.0137 secs, 0.1043 secs
  DNS-lookup:   0.0002 secs, 0.0000 secs, 0.0153 secs
  req write:    0.0001 secs, 0.0000 secs, 0.0131 secs
  resp wait:    0.0781 secs, 0.0126 secs, 0.1042 secs
  resp read:    0.0001 secs, 0.0000 secs, 0.0019 secs

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
