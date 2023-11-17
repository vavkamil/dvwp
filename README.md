# Damn Vulnerable WordPress

Playground for WordPress hacking and [wpscan](https://github.com/wpscanteam/wpscan) testing.

**DO NOT EXPOSE THIS TO INTERNET!**

## Installation

```
$ git clone https://github.com/vavkamil/dvwp.git
$ cd dvwp/
$ docker-compose up -d --build
$ docker-compose run --rm wp-cli install-wp
```

## Usage
```
$ docker-compose up -d
$ docker-compose down
```

## Shell
`docker exec -ti dvwp_wordpress_1 /bin/bash`

## Interface

* [http://127.0.0.1:31337](http://127.0.0.1:31337)
* [http://127.0.0.1:31337/wp-login.php](http://127.0.0.1:31337/wp-login.php)
* [http://127.0.0.1:31338/phpmyadmin/](http://127.0.0.1:31338/phpmyadmin/)

## Credentials
* Wordpress: admin/admin
* MySQL: root/password

## Vulnerabilities

Feel free to contribute with pull requests ;)

### Plugins

* [InfiniteWP Client < 1.9.4.5 - Authentication Bypass](https://wpvulndb.com/vulnerabilities/10011)
  - CVE-2020-8772

* [WordPress File Upload < 4.13.0 - Directory Traversal to RCE](https://wpvulndb.com/vulnerabilities/10132)
  - CVE-2020-10564

* [WP Advanced Search < 3.3.4 - Unauthenticated Database Access and Remote Code Execution](https://wpvulndb.com/vulnerabilities/10115)
  - no CVE

* [Social Warfare <= 3.5.2 - Unauthenticated Arbitrary Settings Update](https://wpvulndb.com/vulnerabilities/9238)
  - CVE-2019-9978

* [Backup and Staging by WP Time Capsule < 1.21.16 - Authentication Bypass](https://wpvulndb.com/vulnerabilities/10010)
  - CVE-2020-8771
  - NOT WORKING RIGHT NOW

### Otherz

* Directory listing
* display_errors
* info.php
* dump.sql
* adminer.php
* search-replace-db
* cross-domain

## TODO
1. Add versions and description to each vulnerability in README.md
2. Upload docker image to Docker Hub registry
3. Get rid of the Dockerfile
4. Run wp-cli automatically during build
5. Use "svn co" or "wp-cli" to download vulnerable plugins directly
6. Add more vulnerable plugins/themes
7. Update WP and php to latest
8. Add vulnerable phpmyadmin?
9. Add script to pull `access.log` and `error.log` from container
