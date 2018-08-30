<?php
namespace solax_php;


trait SolaxScraperHTTPHelper {

        protected function makeURL( string $url, string $scheme) : string {
                $url = sprintf('%s://%s/%s', $scheme, $this->host, $url);
		return $url;
        }
        protected function buildRequest (string $endpoint, string $method = 'POST', string $scheme = 'http') : CURL{

                $c = new Curl();
                $c->setOptArray([
                        CURLOPT_URL =>
                                $this->makeURL($endpoint, $scheme),
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_POST =>  ($method == 'POST' ? 1 : 0),
                        CURLOPT_HTTPHEADER => $this->headers
                        ]);
                return $c;
        }


        protected function getResponse( CURL $c ) : object {
                if ( ( $response = $c->exec() ) !== false && !is_null($response) ){
			
                        $ret = json_decode($response);
			if ( is_null($ret) ){
				return (object)['response' => $response];
				//print "json failed\n\n";
				//$ret->id = 1;
				//return $ret;
				throw new Exception('Json decode failed: $response');
			}
			return $ret;
                } else {
                        throw new Exception(sprintf('Request failed! %s', $response));
                }
        }
}
