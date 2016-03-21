## Habanero

## Requirements
| Prerequisite    | How to check | How to install
| --------------- | ------------ | ------------- |
| PHP >= 5.6.x    | `php -v`     | [php.net](http://php.net/manual/en/install.php) |
| Composer >= 1.0 | `composer -V`| [composer.org](https://getcomposer.org/download/) |
| Node.js 4.4.x   | `node -v`    | [nodejs.org](http://nodejs.org/) |
| gulp >= 3.9.0   | `gulp -v`    | `npm install -g gulp` |
| Bower >= 1.7.7  | `bower -v`   | `npm install -g bower` |

## Instalation
1. Install [composer](https://getcomposer.org/download/) globally.
2. Install [gulp](http://gulpjs.com) and [Bower](http://bower.io/) globally with `npm install -g gulp bower`
3. Clone repository with `git clone git@github.com:johnzuk/habanero.git`
4. Run `composer install`
5. Copy **config.yaml.dist** to **config.yaml**
6. Set valid parameters in to config.yaml: database name etc.
7. Set your access rights on cache `chmod 777 cache/`
8. Create schema with `php vendor/bin/doctrine orm:schema-tool:create`
9. Go to web/
10. Run `npm install`
11. Run `bower install`
12. Run `gulp`

**Done**