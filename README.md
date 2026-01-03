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
  Total:        0.6831 secs
  Slowest:      0.1237 secs
  Fastest:      0.0184 secs
  Average:      0.0633 secs
  Requests/sec: 1463.9128
  
  Total data:   982000 bytes
  Size/request: 982 bytes

Response time histogram:
  0.018 [1]     |
  0.029 [4]     |■
  0.039 [36]    |■■■■■
  0.050 [98]    |■■■■■■■■■■■■
  0.061 [320]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.071 [274]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.082 [164]   |■■■■■■■■■■■■■■■■■■■■■
  0.092 [69]    |■■■■■■■■■
  0.103 [22]    |■■■
  0.113 [11]    |■
  0.124 [1]     |


Latency distribution:
  10% in 0.0481 secs
  25% in 0.0541 secs
  50% in 0.0619 secs
  75% in 0.0720 secs
  90% in 0.0817 secs
  95% in 0.0900 secs
  99% in 0.1056 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0009 secs, 0.0184 secs, 0.1237 secs
  DNS-lookup:   0.0004 secs, 0.0000 secs, 0.0403 secs
  req write:    0.0003 secs, 0.0000 secs, 0.0164 secs
  resp wait:    0.0588 secs, 0.0091 secs, 0.0906 secs
  resp read:    0.0003 secs, 0.0000 secs, 0.0225 secs

Status code distribution:
  [200] 1000 responses


```

### Built-in PHP server (no-debug mode)
```
➜  bin/dev -d -p // default
➜  bin/hey -n 1000 -c 100 http://localhost:8080

Summary:
  Total:        0.7937 secs
  Slowest:      0.0973 secs
  Fastest:      0.0157 secs
  Average:      0.0751 secs
  Requests/sec: 1259.9747
  

Response time histogram:
  0.016 [1]     |
  0.024 [21]    |■■
  0.032 [11]    |■
  0.040 [11]    |■
  0.048 [15]    |■
  0.056 [18]    |■
  0.065 [8]     |■
  0.073 [86]    |■■■■■■
  0.081 [541]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.089 [277]   |■■■■■■■■■■■■■■■■■■■■
  0.097 [11]    |■


Latency distribution:
  10% in 0.0674 secs
  25% in 0.0744 secs
  50% in 0.0789 secs
  75% in 0.0815 secs
  90% in 0.0832 secs
  95% in 0.0853 secs
  99% in 0.0901 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0008 secs, 0.0157 secs, 0.0973 secs
  DNS-lookup:   0.0007 secs, 0.0000 secs, 0.0153 secs
  req write:    0.0000 secs, 0.0000 secs, 0.0007 secs
  resp wait:    0.0742 secs, 0.0146 secs, 0.0872 secs
  resp read:    0.0001 secs, 0.0000 secs, 0.0008 secs

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
