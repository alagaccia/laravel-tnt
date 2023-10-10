<?php
namespace AndreaLagaccia\Tnt;

use AndreaLagaccia\Tnt\Tnt;
use Spatie\ArrayToXml\ArrayToXml;

class Shipping extends Tnt
{
    protected $url;
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
        $this->movement = $data['movement'];

        try {
            $soap = new \SoapClient($this->url);

            $xml = $this->XML_create();
            $res = $soap->__soapCall('getPDFLabel', [['inputXml' => $xml]]);

            if ( ! $res->getPDFLabelReturn->documentCorrect ) {
                $xml = simplexml_load_string($res->getPDFLabelReturn->outputString, "SimpleXMLElement", LIBXML_NOCDATA);
                $json = json_encode($xml);
                $array = json_decode($json,TRUE);
                
                abort(500, $array['Message']);
            } else {
                return [
                    'label_request' => $xml,
                    'label_response' => $res->getPDFLabelReturn->outputString,
                ];
            }
        } catch (\SoapFault $e) {
            return $e->getMessage();
        }
    }

    public function print($consignmentno)
    {
        $this->consignmentno = $consignmentno;

        try {
            $soap = new \SoapClient($this->url);

            $xml = $this->XML_print();
            $res = $soap->__soapCall('getPDFLabel', [['inputXml' => $xml]]);

            if ( ! $res->getPDFLabelReturn->documentCorrect ) {
                dd($res->getPDFLabelReturn->outputString);
            } else {
                // header('Content-Description: File Transfer');
                // header('Content-Type: application/pdf');
                // header('Content-Disposition: attachment; filename="Label.pdf"');
                // header('Expires: 0');
                return $res->getPDFLabelReturn->binaryDocument;
            }
        } catch (\SoapFault $e) {
            return $e->getMessage();
        }
    }

    public function destroy($consignmentno)
    {
        $this->consignmentno = $consignmentno;

        try {
            $xml = $this->XML_delete();

            $soap = new \SoapClient($this->url);
            $res = $soap->__soapCall('getPDFLabel', [['inputXml' => $xml]]);

            // In caso di cancellazione documentCorrect è false
            if ( ! $res->getPDFLabelReturn->documentCorrect ) {
                return $res->getPDFLabelReturn->outputString;
            } else {
                dd($res->getPDFLabelReturn->outputString);
            }

        } catch (\SoapFault $e) {
            return $e->getMessage();
        }
    }

    public function xml_root()
    {
        return [
            'rootElementName' => 'shipment',
            '_attributes' => [
                'xsi:noNamespaceSchemaLocation' => 'c:routinglabel.xsd',
                'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            ]
        ];
    }

    public function XML_create()
    {
        $body = [
            'software' => [
                'application' => 'MYRTL', // MYRTLI = internazionale
                'version' => '1.0',
            ],
            'security' => $this->security(),
            'consignment' => [
                '_attributes' => [
                    'action' => "I", // insert
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
                'actualweight' => str_pad($this->movement->total_weight, 8, "0", STR_PAD_LEFT), // variabile in grammi
                'totalpackages' => $this->movement->total_boxes, // variabile
                'packagetype' => 'C', // C= Colli, S= Buste; B Bauletti piccoli; D Bauletti grandi
                
                'division' => '',
                'product' => 'N',
                'collectiondate' => $this->movement->collection_date?->format('Ymd') ?? now()->format('Ymd'), // data di affidamento a spedizione YYYYMMDD
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
                        'itemtype' => 'S', // C collo, S buste, B bauletti piccoli, D Bauletti grandi
                        'weight' => str_pad($this->movement->total_weight, 8, "0", STR_PAD_LEFT), // grammi
                        'quantity' => $this->movement->total_boxes,
                    ]
                ],
            ]
        ];

        $xml = ArrayToXml::convert($body, $this->xml_root(), true, 'UTF-8', '1.0', []);

        return $xml;
    }

    public function XML_print()
    {
        $body = [
            'software' => [
                'application' => 'MYRTL', // MYRTLI = internazionale
                'version' => '1.0',
            ],
            'security' => $this->security(),
            'consignment' => [
                '_attributes' => [
                    'action' => "R", // delete
                ],
                'senderAccId' => "{$this->senderAccId}",
                'consignmentno' => $this->consignmentno ?? null, // Alphanumeric <=15 digit
                'consignmenttype' => 'T', // C = chiave fornita dal client, T = fornita da TNT
            ]
        ];

        $xml = ArrayToXml::convert($body, $this->xml_root(), true, 'UTF-8', '1.0', []);

        return $xml;
    }

    public function XML_delete()
    {
        $body = [
            'software' => [
                'application' => 'MYRTL', // MYRTLI = internazionale
                'version' => '1.0',
            ],
            'security' => $this->security(),
            'consignment' => [
                '_attributes' => [
                    'action' => "D", // delete
                ],
                'senderAccId' => "{$this->senderAccId}",
                'consignmentno' => $this->consignmentno ?? null, // Alphanumeric <=15 digit
                'consignmenttype' => 'T', // C = chiave fornita dal client, T = fornita da TNT
            ]
        ];

        $xml = ArrayToXml::convert($body, $this->xml_root(), true, 'UTF-8', '1.0', []);

        return $xml;
    }

    public function sender()
    {
        return [
            'addressType' => 'S',
            'name' => $this->movement->sender->name,
            'addrline1' => $this->movement->sender->address,
            'town' => $this->movement->sender->town,
            'postcode' => $this->movement->sender->postcode,
            'province' => $this->movement->sender->province,
            'country' => $this->movement->sender->country,
            'phone1' => '',
        ];
    }

    public function collection()
    {
        return [
            'addressType' => 'C',
            'name' => $this->movement->collection->name,
            'addrline1' => $this->movement->collection->address,
            'town' => $this->movement->collection->town,
            'postcode' => $this->movement->collection->postcode,
            'province' => $this->movement->collection->province,
            'country' => $this->movement->collection->country,
            'phone1' => '',
        ];
    }

    public function receiver()
    {
        return [
            'addressType' => 'R',
            'name' => $this->movement->receiver->name . ($this->movement->receiver->at ? " @ {$this->movement->receiver->at}" : null),
            'addrline1' => $this->movement->receiver->address,
            'town' => $this->movement->receiver->town,
            'postcode' => $this->movement->receiver->postcode,
            'province' => $this->movement->receiver->province,
            'country' => $this->movement->receiver->country,
            'phone1' => $this->movement->receiver->phone_code,
            'phone2' => $this->movement->receiver->phone_number,
            'email' => $this->movement->receiver->email,
        ];
    }
}
