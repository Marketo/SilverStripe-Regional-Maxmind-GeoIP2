## Maintainer Contact

Kirk Mayo

<kirk (dot) mayo (at) solnet (dot) co (dot) nz>

## Requirements

* SilverStripe 3.2
* SilverStripe-Regional Module

# SilverStripe-Regional-Maxmind-GeoIP2

Driver allowing the SilverStripe-Regional module to pull data from Maxmind using GeoIP2


## Composer Installation

  composer require marketo/silverstripe-regional-maxmind-geoip2

## Config

You will need to set the location of the database files if they are in the default locations.
This can be done by adding a couple of lines to the config.yml file as per the example below.

```
IPInfoCache:
  GeoPath: '/usr/share/GeoIP/GeoLite2-City.mmdb'
  GeoPathISP: '/usr/share/GeoIP/GeoIP2-ISP-Test.mmdb'
```

## GeoIP database

You will neeed to retrive a databse for the module to work with this will need to be stored
on the server and you may need to set the location of GeoPath under IPInfoCache in your config yml file.
The free databases can be downloaded from here <https://github.com/maxmind/GeoIP2-php>
You can also generate test databases with this module <https://github.com/maxmind/MaxMind-DB>

## API endpoints

The curent endpoint returns a JSON object giving location details for the IP address.
The results default to json but they can also be returned as jsonp if this has been defined under
the config for IPInfoCache

```
http://YOURSITE/geoip/IPADDRESS
http://YOURSITE/geoip/IPADDRESS.json
http://YOURSITE/geoip/IPADDRESS.jsonp
```

## TODO

Add tests
Split up conection methods make it easy to use other connectors and dbs
