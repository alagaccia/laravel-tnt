<?php
namespace AndreaLagaccia\Tnt;

use AndreaLagaccia\Tnt\Tnt;
use Spatie\ArrayToXml\ArrayToXml;

class Shipping extends Tnt
{
    protected $url;
    protected $data;
    protected $movement;
    protected $consignmentno;

    /*
     * Il metodo getPDFLabel della classe ResiServiceImpl, riceve come parametro la stringa xml di input
     * relativa al servizio MyRTL e restituisce un oggetto Document che contiene a sua volta i seguenti oggetti:
     * 
     * documentCorrect: è un booleano (true/false). Se é uguale a true la transazione é corretta e l’oggetto Document contiene anche il PDF
     * binaryDocument: è un documento PDF relativo alle etichette ed é presente solo se l’oggetto documentCorrect è = true.
     * outputString: è la stringa di output completa restituita dal servizio.
     * 
     *  ----------------------------------
     * 
     * consignmentno
     * consignmentno é la chiave della chiamata riferita alla singola spedizione. Questa verrá
     * usata come referenza univoca ogniqualvota sará necessario modificare i dati di spedizione.
     * Puó essere fornita da TNT o dichiarata dal cliente
     */ 

    public function __construct($credentials = null)
    {
        parent::__construct($credentials);
        
        $this->url = 'https://www.mytnt.it/ResiService/ResiServiceImpl.wsdl';
    }

    public function store($data)
    {
        $this->data = $data;
        $this->movement = $data['movement'];

        try {
            $soap = new \SoapClient($this->url);

            $res = $soap->__soapCall('getPDFLabel', [['inputXml' => $this->createXML(type: "INSERT")]]);

            if ( ! $res->getPDFLabelReturn->documentCorrect ) {
                dd($res->getPDFLabelReturn->outputString);
            } else {
                return $res->getPDFLabelReturn->outputString;
            }
        } catch (\SoapFault $e) {
            header('Content-Type: text/html');
            header('Expires: 0');
            print_r($e);
        }
    }

    public function print($xml)
    {
        try {
            $soap = new \SoapClient($this->url);

            $arrayXml = json_decode($xml, associative: true);
            // Sostituire type of action con PRINT

            $res = $soap->__soapCall('getPDFLabel', [['inputXml' => $this->createXML(type: "PRINT")]]);

            if ( ! $res->getPDFLabelReturn->documentCorrect ) {
                dd($res->getPDFLabelReturn->outputString);
            } else {
                return $res->getPDFLabelReturn->outputString;
            }
        } catch (\SoapFault $e) {
            header('Content-Type: text/html');
            header('Expires: 0');
            print_r($e);
        }
    }

    public function destroy($consignmentno)
    {
        $this->consignmentno = $consignmentno;

        try {
            $soap = new \SoapClient($this->url);

            $res = $soap->__soapCall('getPDFLabel', [['inputXml' => $this->createXML(type: "DELETE")]]);

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

    public function createXML($type = "INSERT")
    {
        $typeOfAction = [
            "INSERT" => "I",
            "EDIT" => "M",
            "DELETE" => "D",
            "PRINT" => "R",
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
                    'operationaloption' => '0', // 1= Held in TNT depot, 2 = Held at drop-off, 3 = delivery on appointment, 4 = delivery on LockerBox
                    'highvalue' => 'N',
                    'specialgoods' => 'N',
                ],
                'senderAccId' => "{$this->senderAccId}",
                'consignmentno' => $this->consignmentno ?? null, // Alphanumeric <=15 digit
                'consignmenttype' => 'T', // C = chiave fornita dal client, T = fornita da TNT
                
                // colli
                'actualweight' => '00001500', // variabile in grammi
                'totalpackages' => '1', // variabile
                'packagetype' => 'C', // C= Colli, S= Buste; B Bauletti piccoli; D Bauletti grandi
                
                'division' => '',
                'product' => 'N',
                'collectiondate' => now()->format('Ymd'), // data di affidamento a spedizione YYYYMMDD
                'termsofpayment' => 'S', // S = mittente, R = destinatario
                'systemcode' => 'RL', // fisso
                'systemversion' => '1.0', // fisso

                'addresses' => [
                    [ 'address' => $this->sender() ],
                    [ 'address' => $this->collection() ],
                    [ 'address' => $this->receiver() ],
                ],
                'dimensions' => [
                    [
                        '_attributes' => [
                            'itemaction' => 'I', // I inserimento, D cancellazione, R ristampa
                        ],
                        'itemtype' => 'C', // C collo, S buste, B bauletti piccoli, D Bauletti grandi
                        'weight' => '00001500', // grammi
                        'quantity' => '1',
                    ]
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
        ];
    }

    public function sender()
    {
        return [
            'addressType' => 'S',
            'name' => $this->movement->sender['business_name'],
            'addrline1' => $this->movement->sender['address_street'],
            'addrline2' => '',
            'town' => $this->movement->sender['address_city'],
            'postcode' => $this->movement->sender['address_postcode'],
            'province' => $this->movement->sender['address_province'],
            'country' => $this->movement->sender['address_country'],
            'phone1' => '',
        ];
    }

    public function collection()
    {
        return [
            'addressType' => 'C',
            'name' => $this->movement->collection['name'],
            'addrline1' => $this->movement->collection['address_street'],
            'addrline2' => $this->movement->collection['address_at'] ?? null,
            'town' => $this->movement->collection['address_city'],
            'postcode' => $this->movement->collection['address_postcode'],
            'province' => $this->movement->collection['address_province'],
            'country' => $this->movement->collection['address_country'],
            'phone1' => '',
        ];
    }

    public function receiver()
    {
        return [
            'name' => $this->movement->receiver['name'],
            'addrline1' => $this->movement->receiver['address_street'],
            'addrline2' => $this->movement->receiver['address_at'] ?? null,
            'town' => $this->movement->receiver['address_city'],
            'postcode' => $this->movement->receiver['address_postcode'],
            'province' => $this->movement->receiver['address_province'],
            'country' => $this->movement->receiver['address_country'],
            'phone1' => $this->movement->receiver['phone'],
        ];
    }
}
