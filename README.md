# Damn Vulnerable WordPress

Playground for WordPress hacking and [wpscan](https://github.com/wpscanteam/wpscan) testing.

## Installation

```
$ git clone https://github.com/vavkamil/dvwp.git
$ cd dvwp/
$ docker-compose up -d --build
```

## Usage
```
$ docker-compose up
$ docker-compose down
```

## Shell
`docker exec -ti dvwp_wp_1 /bin/bash`

## Interface

* [http://127.0.0.1:1337](http://127.0.0.1:1337)
* [http://127.0.0.1:1337/wp-login.php](http://127.0.0.1:1337/wp-login.php)
* [http://127.0.0.1:1337/phpmyadmin/](http://127.0.0.1:1337/phpmyadmin/)

## Credentials
* Wordpress: admin/admin
* MySQL: root/password

## Vulnerabilities

Feel free to contribute with pull requests ;)

* [InfiniteWP Client < 1.9.4.5 - Authentication Bypass](https://wpvulndb.com/vulnerabilities/10011)
  - CVE-2020-8772

* [WordPress File Upload < 4.13.0 - Directory Traversal to RCE](https://wpvulndb.com/vulnerabilities/10132)
  - CVE-2020-10564

* [WP Advanced Search < 3.3.4 - Unauthenticated Database Access and Remote Code Execution](https://wpvulndb.com/vulnerabilities/10115)
  - 

* [Social Warfare <= 3.5.2 - Unauthenticated Arbitrary Settings Update](https://wpvulndb.com/vulnerabilities/9238)
  - CVE-2019-9978

* [Backup and Staging by WP Time Capsule < 1.21.16 - Authentication Bypass](https://wpvulndb.com/vulnerabilities/10010)
  - CVE-2020-8771

* []()
  - 

* []()
  - 

* []()
  - 
