<?php
namespace solax_php;

class PVOutput {

	protected $apiKey;

	protected $endPoints = [
		'addOutput' => 'https://pvoutput.org/service/r2/addoutput.jsp',
		'addStatus' => 'https://pvoutput.org/service/r2/addstatus.jsp',
		'addBatchOutput' => 'https://pvoutput.org/service/r2/addbatchoutput.jsp',
		'addBatchStatus' => 'https://pvoutput.org/service/r2/addbatchstatus.jsp'
	];

	protected $headers = [
	
	];

	public function __construct (string $apiKey, string $systemId) {
	
		$this->headers[] = sprintf('X-Pvoutput-Apikey: %s', $apiKey);
		$this->headers[] = sprintf('X-Pvoutput-SystemId: %s', $systemId);
	}

	
	protected function prepareRequest ( string $url ) : Curl {

		$c = new Curl();
		 $c->setOptArray([
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_POST => 1,
                        CURLOPT_HTTPHEADER => $this->headers,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_CAINFO => __DIR__ . '/../cert/COMODO_RSA_Certification_Authority.pem'
                        ]);
                return $c;

	}

        protected function getResponse( CURL $c ) : string {
                if ( ( $response = $c->exec() ) !== false ){
                        return $response;
                } else {
                        throw new Exception(sprintf('Request failed! %s', $response));
                }
        }


	public function addBatchOutputs (array $data) : void {
		$c = $this->prepareRequest($this->endPoints['addBatchOutput']);
		if ( count($data) > 30 ){
			throw new Exception('max 30 outputs per batch');
		}
		$dataStr = sprintf('data=%s', implode(';', $data));
		$c->setOpt(CURLOPT_POSTFIELDS, $dataStr);	
		print_r($dataStr);
		//exit;
		$response = $this->getResponse($c);
		print_r($response);
	}
	

	public function addBatchStatus(array $data) :  void {
		$max = 30;
		$slices = ceil(count($data) / $max);
		$results = [];
		for ( $s = 0; $s < $slices; $s++ ){
			$results[] = $this->realAddBatchStatus(array_slice($data, ($s * $max), $max)); 
		}
	}

	protected function realAddBatchStatus (array $data) : void {
		$c = $this->prepareRequest($this->endPoints['addBatchStatus']);
		if ( count($data) > 30 ){
			throw new Exception('max 30 status reports');
		}
		//print_r($data);
		$dataStr = sprintf('data=%s', implode(';', $data));
		$c->setOpt(CURLOPT_POSTFIELDS, $dataStr);
		$response = $this->getResponse($c);
		print_r($response);	
	}


}
?>
