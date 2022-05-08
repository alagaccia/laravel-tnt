<?php
namespace AndreaLagaccia\Tnt;

use AndreaLagaccia\Tnt\Tnt;
use Spatie\ArrayToXml\ArrayToXml;

class Tracking extends Tnt
{
    public function __construct()
    {
        parent::__construct();
    }

    public function post(array $data, $consignmentno)
    {
        try {
            $res = \Http::post('https://www.mytnt.it/XMLServices', [
                'xmlin' => $this->createXML($consignmentno),
            ]);

            dd($res);
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function createXML($consignmentno)
    {
        $rootElement = [
            'rootElementName' => 'Document',
        ];

        $data = [
            'software' => [
                'application' => 'MYTRA',
                'version' => '2.0',
            ],
            'login' => $this->security(),
            'SearchCriteria' => [
                'ConNo' => $consignmentno,
            ]
        ];

        $xml = ArrayToXml::convert($data, $rootElement, true, 'UTF-8', '1.0', []);

        return $xml;
    }

    public function security()
    {
        return [
            'customer' => "{$this->customer}",
            'user' => "{$this->user}",
            'password' => "{$this->password}",
            'langid' => 'IT',
        ]
    }
}
