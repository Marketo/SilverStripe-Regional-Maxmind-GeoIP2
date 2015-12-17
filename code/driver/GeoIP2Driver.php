<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A cache of geo data for a IP address
 */
use GeoIp2\Database\Reader;

class GeoIP2Driver
{
    public $defaultPath = '/usr/share/GeoIP/GeoLite2-City.mmdb';
    public $defaultPathISP = '/usr/share/GeoIP/GeoIP2-ISP-Test.mmdb';
    public $json;

    public static $statuses = array (
        'SUCCESS' => 'Success',
        'SUCCESS_CACHED' => 'Successfully found and cached response',
        'IP_ADDRESS_INVALID' => 'You have not supplied a valid IPv4 or IPv6 address',
        'IP_ADDRESS_RESERVED' => 'You have supplied an IP address which belongs to ' .
            'a reserved or private range',
        'IP_ADDRESS_NOT_FOUND' => 'The supplied IP address is not in the database',
        'DOMAIN_REGISTRATION_REQUIRED' => 'The domain of your site is not registered.',
        'DOMAIN_REGISTRATION_REQUIRED' => 'The domain of your site is not registered.',
        'GEOIP_EXCEPTION' => 'GEOIP_EXCEPTION [ERROR]',
        'GEOIP_MISSING' => 'GeoIP module does not exist'
    );

    public static $privateAddresses = array(
        '10.0.0.0|10.255.255.255',
        '172.16.0.0|172.31.255.255',
        '192.168.0.0|192.168.255.255',
        '169.254.0.0|169.254.255.255',
        '127.0.0.0|127.255.255.255'
    );

    public static function getStatuses($code = null) {
        if ($code && isset(self::$statuses[$code])) {
            return self::$statuses[$code];
        }
        return self::$statuses;
    }

    public function processIP($ip) {
        $status = null;
        $path = Config::inst()->get('IPInfoCache', 'GeoPath');
        if (!$path) $path = $this->defaultPath;
        if (!file_exists($path)) {
            user_error('Error loading Geo database', E_USER_ERROR);
        }

        $request['ip'] = $ip;
        $request['type'] = self::ipVersion($ip);
        if ($request['type'] == 'IPv4') {
            $isPrivate = self::isPrivateIP($ip);
            if ($isPrivate) {
                $status = self::setStatus('IP_ADDRESS_RESERVED', null, $status);
                return json_encode(array(
                    'status' => $status
                ));
            }
        }
        $reader = new Reader($path);
        $record = $reader->city($ip);

        $countryCode = null;
        try {
            $result['location']['continent_code'] = $record->continent->code;
            $result['location']['continent_names'] = $record->continent->names;

            $countryCode = $record->country->isoCode;
            $result['location']['country_code'] = $countryCode;
            $result['location']['country_names'] = $record->country->names;

            $result['location']['postal_code'] = $record->postal->code;
            $result['location']['city_names'] = $record->city->names;

            $result['location']['latitude'] = $record->location->latitude;
            $result['location']['longitude'] = $record->location->longitude;
            $result['location']['time_zone'] = $record->location->timeZone;
        } catch (Exception $e) {
            $status = self::setStatus('GEOIP_EXCEPTION', $e, $status);
        }

        $pathISP = Config::inst()->get('IPInfoCache', 'GeoPathISP');
        if (!$pathISP) $pathISP = $this->defaultPathISP;
        if (!file_exists($pathISP)) {
            user_error('Error loading Geo ISP database', E_USER_ERROR);
        }
        $reader = new Reader($pathISP);
        $record = $reader->isp($ip);
        if ($record) {
            $result['organization']['name'] = $record->organization;
            $result['organization']['isp'] = $record->isp;
        }

        if ($status) {
            $statusArray['code'] = self::setStatus(null, null, $status);
            $statusArray['message'] = self::getStatusMessage($status);
            // do not cache a failure
            $this->json = json_encode(array(
                'request' => $request,
                'status' => $statusArray,
                'result' => array('maxmind-geoip2' => $result)
            ));
        } else {
            // return cached success message
            $statusArray['code'] = self::setStatus('SUCCESS_CACHED', null, null);
            $statusArray['message'] = self::getStatusMessage($statusArray['code']);
            $this->json =  json_encode(array(
                'request' => $request,
                'status' => $statusArray,
                'result' => array('maxmind-geoip2' => $result)
            ));
        }

        // we write a different json object with a cached status to the DB
        $statusArray['code'] = self::setStatus('SUCCESS', null, $status);
        $statusArray['message'] = self::getStatusMessage($statusArray['code']);
        $dbJson = json_encode(array(
            'request' => $request,
            'status' => $statusArray,
            'result' => array('maxmind-geoip2' => $result)
        ));

        return $dbJson;
    }

    public static function setStatus($code, $e, $status = null) {
        if ($status) return $status;
        if ($code == 'GEOIP_EXCEPTION' && $e && $e instanceof Exception) {
            self::$statuses['GEOIP_EXCEPTION'] = str_replace(
                'ERROR',
                $e->getMessage(),
                self::$statuses['GEOIP_EXCEPTION']
            );
        }
        return $code;
    }

    public static function getStatusMessage($status) {
        if (!$status) $status = 'SUCCESS_CACHED';
        return self::$statuses[$status];
    }

    public function getDetails() {
        return $this->Info;
    }

    public function getJSON() {
        return $this->json;
    }

    public function clearIPCache() {
        $this->write(false, false, true);
    }

    public static function ipVersion($ip = null) {
        return (strpos($ip, ':') === false) ? 'IPv4' : 'IPv6';
    }

    public static function isPrivateIP($ip) {
        $longIP = ip2long($ip);
        if ($longIP != -1) {
            foreach (self::$privateAddresses as $privateAddress) {
                list($start, $end) = explode('|', $privateAddress);
                if ($longIP >= ip2long($start) && $longIP <= ip2long($end)) return (true);
            }
        }
        return false;
    }
}
