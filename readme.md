

# Installation

```shell
sudo apt install php php-xml php-curl php-zip git unzip
git clone https://github.com/Kehet/rss-torrent-downloader.git
cd rss-torrent-downloader

EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
rm composer-setup.php

php composer.phar install
```

## Crontab

```
2 * * * * /usr/bin/php /home/kehet/rss-torrent-downloader/fetch-rss.php > /home/kehet/rss-torrent-downloader/fetch-rss.log 2>&1
5 * * * * /usr/bin/php /home/kehet/rss-torrent-downloader/remove-done.php > /home/kehet/rss-torrent-downloader/remove-done.log 2>&1
```

