<?php
namespace AndreaLagaccia\Tnt;

use AndreaLagaccia\Tnt\Tnt;
use Spatie\ArrayToXml\ArrayToXml;
use Illuminate\Support\Facades\Http;

class Tracking extends Tnt
{
    protected $url;

    public function __construct()
    {
        parent::__construct();
        
        $this->url = 'https://www.mytnt.it/XMLServices';
    }
    
    public function get($consignmentno)
    {
        try {
            $res = Http::asForm()->post('https://www.mytnt.it/XMLServices', [
                'xmlin' => $this->createTrackingXML($consignmentno),
            ]);
            $response = $res->body();

            $xml =  simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);

            $json = json_encode($xml);
            $array = json_decode($json, true);
        
            return $array;

        } catch (\Exception $e) {
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
