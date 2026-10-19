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

You will need `PHP 8.2+`, [Composer](https://getcomposer.org/) and [NodeJS](https://nodejs.org/).

Create project using composer.

```bash
composer create-project -s dev contributte/demo-frankenphp acme
```

Now you have application installed. It's time to run it.

## Startup

### Build dependencies & assets

- Install PHP dependencies.

```bash
composer install
```

- Install NodeJS dependencies & build assets.

```bash
npm install
npm run tailwind
npm run build
```

- Spin up Docker stack. FrankenPHP with Caddyserver.

```bash
bin/dev --franken -d --port=448 // frankenphp on 448 in detached mode. In https by default under FrankenPHP
bin/dev --franken -d -b -p --port=888 // needs docker container re-build on port or environnement changes
```

```
// to compare with php built-in server
bin/dev -d // on 8000 by default in detached mode

// stop all
bin/dev stop

// see usage
bin/dev --help
```


Then visit [http://localhost:8000](http://localhost:8000) in your browser.

List of URL's:

- [http://localhost:8000/](http://localhost:8000)
- [http://localhost:8000/api](http://localhost:8000/api)
- [http://localhost:8000/api/phpinfo](http://localhost:8000/api/phpinfo)


## Benchmark (with [hey](https://github.com/rakyll/hey) - no debug, no xdebug)

### FrankenPHP

```
➜ bin/dev -f --port=443 -d -p // frankenphp on 443 to solve tls issues
➜ bin/hey -n 1000 -c 100 https://localhost

Summary:
  Total:        0.2190 secs
  Slowest:      0.1247 secs
  Fastest:      0.0017 secs
  Average:      0.0204 secs
  Requests/sec: 4567.2205


Response time histogram:
  0.002 [1]     |
  0.014 [509]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.026 [341]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.039 [45]    |■■■■
  0.051 [2]     |
  0.063 [19]    |■
  0.076 [13]    |■
  0.088 [40]    |■■■
  0.100 [25]    |■■
  0.112 [1]     |
  0.125 [4]     |


Latency distribution:
  10% in 0.0054 secs
  25% in 0.0084 secs
  50% in 0.0138 secs
  75% in 0.0196 secs
  90% in 0.0564 secs
  95% in 0.0867 secs
  99% in 0.0933 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0027 secs, 0.0017 secs, 0.1247 secs
  DNS-lookup:   0.0004 secs, 0.0000 secs, 0.0484 secs
  req write:    0.0002 secs, 0.0000 secs, 0.0077 secs
  resp wait:    0.0113 secs, 0.0016 secs, 0.0652 secs
  resp read:    0.0030 secs, 0.0000 secs, 0.0239 secs

Status code distribution:
  [200] 1000 responses

```

### Built-in PHP server

```
➜  bin/dev -d -p // default
➜  bin/hey -n 1000 -c 100 http://localhost:8000

Summary:
  Total:        0.9716 secs
  Slowest:      0.1152 secs
  Fastest:      0.0234 secs
  Average:      0.0914 secs
  Requests/sec: 1029.2711

  Total data:   1170000 bytes
  Size/request: 1170 bytes

Response time histogram:
  0.023 [1]     |
  0.033 [29]    |■■
  0.042 [20]    |■
  0.051 [16]    |■
  0.060 [13]    |■
  0.069 [8]     |■
  0.078 [13]    |■
  0.088 [16]    |■
  0.097 [557]   |■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
  0.106 [301]   |■■■■■■■■■■■■■■■■■■■■■■
  0.115 [26]    |■■


Latency distribution:
  10% in 0.0786 secs
  25% in 0.0940 secs
  50% in 0.0953 secs
  75% in 0.0990 secs
  90% in 0.1007 secs
  95% in 0.1044 secs
  99% in 0.1064 secs

Details (average, fastest, slowest):
  DNS+dialup:   0.0004 secs, 0.0234 secs, 0.1152 secs
  DNS-lookup:   0.0002 secs, 0.0000 secs, 0.0233 secs
  req write:    0.0000 secs, 0.0000 secs, 0.0009 secs
  resp wait:    0.0910 secs, 0.0222 secs, 0.1064 secs
  resp read:    0.0000 secs, 0.0000 secs, 0.0005 secs

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
