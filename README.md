# Pebble Movies Data Server (Movie Showtime Aggregator)

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
    * `BING_API_KEY`: API Key for Bing map services, see instructions here: https://msdn.microsoft.com/en-us/library/ff428642.aspx on how to create Bing API key
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


# Server Concepts

The main purpose of the server is to offset the heavy logic involved in data gathering from the device, since most
phones have limited memory, and processing power. It is also important because parts of the application can be
modified without having to touch the user app.

The server can be used with other types of application, not just Pebble smartwatch application, the data is format is client agnostic.

The server performs the following functions:

- Device Registration &amp; Authentication
- Location lookup based on Lat-Long or a combination of user city or postal (zip) code & country.
- Distance estimation between user address and a given theatre; and
- The most important feature, data scraping from different showtime listing sites / API.

The various components/features of the server are discussed below:

## Device Authentication

The purpose of device registration & authentication is to prevent unwarranted calls to the server, the application is
  hosted on a private server and ideally only call should be made by each device per date.

### Registration
In order to avoid exploitation of the  server, this process was added. Every device has to register itself on the server on first-run. The device must . On registration, a unique device ID and a secret key, for signing requests, are returned.

### Authentication
 Every request to the server must be authenticated. The authentication is sent as a `token` URL parameter with every
 request. in development mode, this can be skipped by specifying query paramter: `skip=1`. The structure of the token
  is:

  ```
  token={requestId}|{deviceID}|{sign=sha1(requestId.deviceId.secretKey)}
  ```

   The components of the token are delimited with `|` and they are:

   - `requestId`: The current UNIX timestamo in microseconds. e.g JavaScript's `Date.now()`, must be current and must not be unique for all requests sent by a device.
   - `deviceId`: The ID returned from registration.
   - `sign`: SHA-1 of concatentation `requestId`+`deviceId`+`secretKey`. The `secretkey` is that which was returned at registration.


## Service Providers

The service providers are components that are used to aggregate data within the application, since this application scrapes data from various websites and uses free version of different APIs, it is necessary to limit how much requests it makes to any given API service or website. This is in order to be fair. Hence,the service provider API allows the application to randomly select services which it uses to accomplish different tasks.

 The two major types of service providers used within this application are:

- Showtime Service Providers; and
- Location Service Providers.

All service providers extend the base class: `models\services\ServiceProvider`

### Showtime Service Providers

Showtime service provider are services that are used to aggregate showtime. The way the provider aggregates its data doesn't matter to the `ShowtimeService`. Every service provider should extend the base class `models\services\ShowtimeServiceProvider` class and must be saved in the `models\services\showtimeproviders` name space.

The following showtime providers are currently implemented:
   - Scraper for http://www.imdb.com, defined in `models\services\showtimeproviders\IMDBScraper`
   - Scraper for Google Movies, https://www.google.com/movies, defined in `models\services\showtimeproviders\GoogleMovies`

Please note that for providers that use scrapers, the slightest change in page structure might affect the data integrity, so use with caution.

 DISCLAIMER: What you do with the data scraped from these websites is up to you and you bare full liability. Please check each site's policy and ensure that your usage is in accordance.

### Location Service Providers

The location service providers are needed basically for looking up Long/Lat pairs as addresses. A location service provider should extend the base class:  `models\services\LocationServiceProvider` and be defined in the `models\services\locationproviders` namespace.

The base function of the location service provider can be extended to include other behavious specified by the following interfaces:

  - `models\services\AddressLookupI` : which specifies that a service provider can also look up addresses, and convert them to Long/Lat pairs.
  -  `models\services\LocationDistanceCheckI`: which specifies that a service provider can also compute the distance between two points. Services implementing this method, use actual physical distance based on map directions to indicate compute distance between two points. This is more accurate than using [Sperical geometry](https://en.wikipedia.org/wiki/Spherical_trigonometry) ([Haversine formula](https://en.wikipedia.org/wiki/Haversine_formula)). Note taht using this distance is a bit slower, so it might be easier to just use plain old mathematics.

Most location service providers require that you identify your application using an API key, so please register for these keys to use application. See [section on set up](#setup) above.

Currently providers for the following services are implemented:

  - [MapQuest](http://developer.mapquest.com)
  - [Geonames](http://www.geonames.org/)
  - [Google Maps](https://developers.google.com/maps/documentation/geocoding/get-api-key)
  - [Bing Maps](https://msdn.microsoft.com/en-us/library/ff428642.aspx)

## REST API Documentation

The available calls for the REST API are listed in the below. Please note that every request must contain the following parameters:

 - `token`: [Authentication token](#device-authentication), this should be the current timestamp.
 - `version`: Not compulsory, but needed for Pebble applications to tell if data should be compacted or not.

Other common parameters:
 - `date`: Current date on client, must be a date parsable by PHP's `strtotime()` function, however, the `YYYY-MM-DD` date format is recommended.
 <a name="location-info"></a>
 - **Location Information**: This can be the parameter:
    * `latlng`: which is a the latitude/longitude pair, separated by comma, e.g. `6.33221,0.123212`; or
    * a collection of address parameters: `postalCode`, `city`, & `country`, the `country` must be specified, alongside either postalCode, city or both.
    The `latlng` parameter will always take precedence over  address parameters.




### POST /proxy/register

Registers a new device

**Parameters**
- `device_uuid` A unique identitier string for your device

### GET /proxy/preload

Loads all the data needed for a specific date in one swoop

**Parameters**
The request must contain the [location information](#location-info), the date parameter should also be specified, if left out, the current date on the server will be used.

### GET /proxy/preload11

This is similar to `/proxy/preload`, except that the data returned is more compact in this case. The parameters are the same as those of `/proxy/preload`

### GET /proxy/movies

This will load all the movies currently showing on the specified date in the specified location.

**Parameters**
The request must contain the [location information](#location-info), the date parameter should also be specified, if left out, the current date on the server will be used.


### GET /proxy/theatres

This will load all the theatres near the specified location that are showing movies for the specified date..

**Parameters**
The request must contain the [location information](#location-info), the date parameter should also be specified, if left out, the current date on the server will be used.


### GET /proxy/movie-theatres

This will load all the theatres near the specified location that are showing the specified movie on the specified date.

**Parameters**
The request must contain the [location information](#location-info), the date parameter should also be specified, if left out, the current date on the server will be used. In addition:

  - `movie_id`: The ID of the movie for which you want to retrieve theatres, must be specified.

### GET /proxy/theatre-movies

This will load all the movies currently showing on the specified date in the specified theatre around the specified loaction.

**Parameters**
The request must contain the [location information](#location-info), the date parameter should also be specified, if left out, the current date on the server will be used.  In addition:

- `theatre_id`: The ID of the theatre for which you want to retrieve movies, must be specified.

### GET /proxy/qr

This returns a PBI (Pebble Bitmat Image) representing QRCode for the URL to purchase tickets for the specified showtime.

**Parameters**

- `showtime_id`: the ID for the showtime, this showtime must have a URL attached to it, otherwise, an empty image will be returned.

### GET /proxy/qr-png

Similar to `/proxy/qr` except in this case, a PNG file is returned.


# LICENSE

MIT license