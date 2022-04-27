<?php
namespace AndreaLagaccia\Tnt;

use AndreaLagaccia\Tnt\Tnt;
use Spatie\ArrayToXml\ArrayToXml;

class Shipping extends Tnt
{
    public function __construct()
    {
        parent::__construct();
    }

    public function store(array $data, $consignmentno)
    {
        /* consignmentno
         * consignmentno é la chiave della chiamata riferita alla singola spedizione. Questa verrá
         * usata come referenza univoca ogniqualvota sará necessario modificare i dati di spedizione.
         * Puó essere fornita da TNT o dichiarata dal cliente
        */

        try {
            $soap = new \SoapClient($this->soap_url);

            $res = $soap->__soapCall('getPDFLabel', [['inputXml' => $this->createXML($type = "INSERT", $consignmentno)]]);

            if ( ! $res->getPDFLabelReturn->documentCorrect ) {
                dd($res->getPDFLabelReturn->outputString);
            }
        } catch (\SoapFault $e) {
            header('Content-Type: text/html');
            header('Expires: 0');
            print_r($e);
        }
    }

    public function destroy()
    {
        try {
            $soap = new \SoapClient($this->soap_url);

            $res = $soap->__soapCall('getPDFLabel', [['inputXml' => $this->createXML($type = "DELETE", $consignmentno)]]);

            if ( ! $res->getPDFLabelReturn->documentCorrect ) {
                dd($res->getPDFLabelReturn->outputString);
            }

            dd($res);
            // $xml_res = simplexml_load_string($res->getPDFLabelReturn->outputString);
            // $json = json_encode($xml_res);
            // $res = json_decode($json);
            // dump($obj->Complete->TNTConNo);

            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Label.pdf"');
            header('Expires: 0');
            echo $res->getPDFLabelReturn->binaryDocument;

        } catch (\SoapFault $e) {
            header('Content-Type: text/html');
            header('Expires: 0');
            print_r($e);
        }
    }

    public function createXML($type = "INSERT", $consignmentno)
    {
        $typeOfAction = [
            "INSERT" => "I",
            "DELETE" => "D",
        ];

        $rootElement = [
            'rootElementName' => 'shipment',
            '_attributes' => [
                'xsi:noNamespaceSchemaLocation' => 'c:routinglabel.xsd',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            ]
        ];

        $data = [
            'software' => [
                'application' => 'MYRTL',
                'version' => '1.0',
            ],
            'security' => $this->security(),
            'consignment' => [
                '_attributes' => [
                    'action' => "{$typeOfAction[$type]}",
                    'international' => 'N',
                    'insurance' => 'N',
                    'hazardous' => 'N',
                    'cashondelivery' => 'N',
                    'codcommission' => 'S',
                    'insurancecommission' => 'S',
                    'operationaloption' => '0',
                    'highvalue' => 'N',
                    'specialgoods' => 'N',
                ],
                'laroseDepot' => '',
                'labelType' => 'P',
                'senderAccId' => "{$this->senderAccId}",
                'consignmentno' => "{$consignmentno}",
                'PrintInstrDocs' => 'N',
                'consignmenttype' => 'C',
                'actualweight' => '00001500',
                'actualvolume' => '0000010',
                'totalpackages' => '1',
                'packagetype' => 'C',
                'division' => '',
                'product' => 'N',
                'vehicle' => 'C',
                'insurancevalue' => '0000000000000',
                'insurancecurrency' => 'EUR',
                'packingdesc' => '',
                'reference' => '123INPUT',
                'collectiondate' => '23032022',
                'collectiontime' => '1500',
                'invoicevalue' => '0000000000000',
                'invoicecurrency' => 'EUR',
                'specialinstructions' => '',
                'options' => [
                    'option' => '',
                ],
                'termsofpayment' => 'S',
                'systemcode' => 'RL',
                'systemversion' => '1.0',
                'codfvalue' => '0000000000000',
                'codfcurrency' => 'EUR',
                'goodsdesc' => '',
                'eomenclosure' => '',
                'eomofferno' => '',
                'eomdivision' => '',
                'eomunification' => '',
                'dropoffpoint' => '',
                'addresses' => [
                    [ 'address' => $this->sender() ],
                    [ 'address' => $this->collection() ],
                    [ 'address' => $this->receiver() ],
                ],
                'dimensions' => [
                    '_attributes' => [
                        'itemaction' => 'I',
                    ],
                    'itemsequenceno' => '00001',
                    'itemtype' => 'C',
                    'itemreference' => '1',
                    'volume' => '',
                    'weight' => '00001500',
                    'length' => '015000',
                    'height' => '020000',
                    'width' =>  '003000',
                    'quantity' => '1',
                ],
                'articles' => [
                    'tariff' => '',
                    'origcountry' => '',
                ],
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

    public function sender()
    {
        return [
            'addressType' => 'S',
            'vatno' => '',
            'addrline1' => 'Via di Novoli 10/2',
            'addrline2' => '',
            'addrline3' => '',
            'postcode' => '50127',
            'phone1' => '',
            'phone2' => '',
            'name' => 'ADK ITALIA SRL',
            'country' => 'IT',
            'town' => 'Firenze',
            'contactname' => '',
            'fax1' => '',
            'fax2' => '',
            'telex' => '',
            'province' => 'FI',
            'custcountry' => '',
            'title' => '',
        ];
    }

    public function collection()
    {
        return [
            'addressType' => 'C',
            'vatno' => '',
            'addrline1' => 'Via Ungheria 23',
            'addrline2' => 'presso AS GROUP SRL',
            'addrline3' => '',
            'postcode' => '50126',
            'phone1' => '',
            'phone2' => '',
            'name' => 'ADK ITALIA SRL',
            'country' => 'IT',
            'town' => 'Firenze',
            'contactname' => '',
            'fax1' => '',
            'fax2' => '',
            'telex' => '',
            'province' => 'FI',
            'custcountry' => '',
            'title' => '',
        ];
    }

    public function receiver()
    {
        return [
            'addressType' => 'R',
            'vatno' => '',
            'addrline1' => 'Piazza della Costituzione, 10',
            'addrline2' => 'Campomigliaio',
            'addrline3' => '',
            'postcode' => '50038',
            'phone1' => ['_cdata' =>'3466197863'],
            'phone2' => '',
            'name' => 'Lagaccia Andrea',
            'country' => 'IT',
            'town' => 'Scarperia e San Piero',
            'contactname' => '',
            'fax1' => '',
            'fax2' => '',
            'telex' => '',
            'province' => 'FI',
            'custcountry' => '',
            'title' => '',
        ];
    }
}
