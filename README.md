# Pebble Movies Data Server


| Pebble movies is based on iDEO, documentation is available at: http://ideo.intelworx.com/ |
|--- |


## Requirements

The following are required to set up this application

- PHP 5.4+
- MySQL 5.1+
- [Composer](https://getcomposer.org/)


## Set up

Set up of this application is pretty straightforward, follow the steps below

* Clone application into desired location

  `git clone https://github.com/logbon72/pebble-movies-server.git /path/to/app`

* Install application dependencies using composer

   `cd /path/to/app && composer install`

* Create database and user, from MySQL console

    ```sql
    CREATE DATABASE dbname CHARACTER SET 'utf-8';
    GRANT ALL ON dbname.* TO username@'host-address' IDENTIFIED BY 'yourPassword';
    ```
* Import database schema from the files in `db` directory, ensure that all files are imported in order.

  ```
    cd db && cat *.sql | mysql -h databseHost -u username -p --database=dbname
  ```

* Set up application variables, by copying the sample environment file to .environment

   `cp .environment.sample .environment`

    The environment file contains key=value pairs for your application, the variable names are self-explanatory, the are also explained below:

    * `CONFIG`: deployment configuration, `dev` for development and `live` for production.
    * `DB_*`: database related configurations, please enter details used in DB creation for these variables
    * `DB_LOG_QUERIES`: `1` to log queries, `0` otherwise
    * `GEONAMES_USERNAME`: Username for [Geonames](http://www.geonames.org/) Address lookup API.
    * `GOOGLE_API_KEY`: API key for Google maps API, get your credentials from here: https://developers.google.com/maps/documentation/geocoding/get-api-key
    * `BING_API_KEY`: API Key for Bing map services, see instructions here:
    https://msdn.microsoft.com/en-us/library/ff428642.aspx on how to create Bing API key
    * `MAP_QUEST_API_KEY`: Visit https://developer.mapquest.com to generate developer keys for MapQuest API.
    * `BITLY_API_KEY`: Bitly API key
    * `BITLY_API_SECRET`: Bitly API secret
    * `BITLY_TOKEN`: Bitly user token. To generate Bitly API credentials, please visit: http://dev.bitly.com

   Once all the necessary keys are entered, you are set to run application.

## Running Application

This application can be run from your web server root, simply copy to a specified directory within your server's
document root, and visit http://localhost:{PORT}/path

Alternatively, you can use the in-built PHP server from the command-line, specifying `server.php` as the router.

```bash

cd /path/to/app && php -S 0.0.0.0:8080 server.php

```

You can replace `8080` with a port number of your choice.
