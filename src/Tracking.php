<?php
namespace AndreaLagaccia\Tnt;

use AndreaLagaccia\Tnt\Tnt;
use Spatie\ArrayToXml\ArrayToXml;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class Tracking extends Tnt
{
    protected $url;
    protected $debug;

    public function __construct($credentials = null, $debug = false)
    {
        parent::__construct($credentials);
        
        $this->debug = $debug;
        $this->url = 'https://www.mytnt.it/XMLServices';
    }
    
    public function get($consignmentno)
    {
        if ( ! $consignmentno ) {
            throw new \Exception("Tracking number missing");
        }
        
        try {
            $requestData = $this->createTrackingXML($consignmentno);

            if ($this->debug) {
                Log::debug('TNT Tracking request', ['data' => $requestData]);
            }

            $res = Http::asForm()->post('https://www.mytnt.it/XMLServices', [
                'xmlin' => $requestData,
            ]);
            $response = $res->body();
            $statusCode = $res->status();

            if ($this->debug) {
                Log::debug('TNT Tracking response', ['status' => $statusCode, 'response' => $response]);
            }

            if (empty($response)) {
                Log::error('TNT Tracking: response body is empty', ['status' => $statusCode]);
                return null;
            }

            $xml = @simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($xml === false) {
                Log::error('TNT Tracking: XML parsing failed', ['response' => $response]);
                return null;
            }

            if ($this->debug) {
                Log::debug('TNT Tracking Response xml', ['data' => $xml]);
            }

            $json = json_encode($xml);
            $array = json_decode($json, true);

            if ($this->debug) {
                Log::debug('TNT Tracking Response array', ['data' => $array]);
            }
        
            return $array;

        } catch (\Exception $e) {
            if ($this->debug) {
                Log::debug('TNT Tracking error', ['error' => $e->getMessage()]);
            }
            return $e->getMessage();
        }
    }

    public function xml_root()
    {
        return [
            'rootElementName' => 'Document',
        ];
    }

    public function createTrackingXML($consignmentno)
    {
        $data = [
            'application' => 'MYTRA',
            'version' => '2.0',
            'login' => $this->security(),
            'SearchCriteria' => [
                'ConNo' => $consignmentno,
            ],
            'SearchParameters' => [
                'SearchType' => 'Detail',
                'SearchOption' => 'ConsignmentTracking',
                'SearchMethod' => 'Forward',
                'ExtraDetails' => 'ConsignmentDetail',
            ]
        ];

        $xml = ArrayToXml::convert($data, $this->xml_root(), true, 'UTF-8', '1.0', []);

        return $xml;
    }
}
