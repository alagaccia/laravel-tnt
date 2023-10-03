<?php
namespace AndreaLagaccia\Tnt;

use AndreaLagaccia\Tnt\Tnt;
use Spatie\ArrayToXml\ArrayToXml;

class Shipping extends Tnt
{
    protected $url;

    public function __construct($credentials = null)
    {
        parent::__construct($credentials);
        
        $this->url = 'https://www.mytnt.it/ResiService/ResiServiceImpl.wsdl';
    }

    public function store(array $data, $consignmentno)
    {
        /* consignmentno
         * consignmentno é la chiave della chiamata riferita alla singola spedizione. Questa verrá
         * usata come referenza univoca ogniqualvota sará necessario modificare i dati di spedizione.
         * Puó essere fornita da TNT o dichiarata dal cliente
        */

        try {
            $soap = new \SoapClient($this->url);

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
            $soap = new \SoapClient($this->url);

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
                'application' => 'MYRTL', // MYRTLI = internazionale
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
                    'codcommission' => 'S', // S = mittente, R = destinatario
                    'insurancecommission' => 'S', // S = mittente, R = destinatario
                    'operationaloption' => '0',
                    'highvalue' => 'N',
                    'specialgoods' => 'N',
                ],
                'senderAccId' => "{$this->senderAccId}",
                'consignmentno' => "{$consignmentno}", // Alphanumeric <=15 digit
                'consignmenttype' => 'C', // C = chiave fornita dal client, T = fornita da TNT
                'actualweight' => '00001500', // variabile in grammi
                'totalpackages' => '1', // variabile
                'packagetype' => 'C', // C= Colli, S= Buste; B Bauletti piccoli; D Bauletti grandi
                'division' => '',
                'product' => 'N',
                'collectiondate' => '23032022', // data di affidamento a spedizione YYYYMMDD
                'termsofpayment' => 'S', // S = mittente, R = destinatario
                'systemcode' => 'RL', // fisso
                'systemversion' => '1.0', // fisso

                'addresses' => [
                    [ 'address' => $this->sender() ],
                    [ 'address' => $this->collection() ],
                    [ 'address' => $this->receiver() ],
                ],
                'dimensions' => [
                    '_attributes' => [
                        'itemaction' => 'I', // I inserimento, D cancellazione, R ristampa
                    ],
                    'itemtype' => 'C', // C collo, S buste, B bauletti piccoli, D Bauletti grandi
                    'weight' => '00001500', // grammi
                    'quantity' => '1',
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
