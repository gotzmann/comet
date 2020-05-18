<?php               
declare(strict_types=1);

namespace Comet;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class Response extends GuzzleResponse implements ResponseInterface
{
    /**
     * @param int                                  $status  Status code
     * @param array                                $headers Response headers
     * @param string|null|resource|StreamInterface $body    Response body
     * @param string                               $version Protocol version
     * @param string|null                          $reason  Reason phrase 
     */
    public function __construct(
        $status = 200,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    ) {
		parent::__construct($status, $headers, $body, $version, $reason);    
    }

    public function with($body, $status = null)
    {
    	if (is_string($body)) {
	  		$stream = fopen('php://memory','r+');
			fwrite($stream, $body);
			rewind($stream);
   			$new = $this->withBody(new Stream($stream));
			
   		} elseif (is_array($body)) {
	        $body = json_encode($body);
	        
	        if ($body === false) {
    	        throw new \RuntimeException(json_last_error_msg(), json_last_error());        	
        	}        

        	$stream = fopen('php://memory','r+');
			fwrite($stream, $body);
			fseek($stream, 0); 
   			$new = $this->withBody(new Stream($stream));
        	$new = $new->withHeader('Content-Type', 'application/json');                	            
        }

        if (isset($status)) {
    	   	$new = $new->withStatus($status);        
        }
   		
   		return $new;        
    }

    // TODO Optimize for performance
    // https://gist.github.com/akrabat/807ccfbef25baafe646ba170ac2277fd
    public function withJson($data, $status = null, $encodingOptions = 0)
    {
        $json = json_encode($data, $encodingOptions);
        if ($json === false) {
            throw new \RuntimeException(json_last_error_msg(), json_last_error());
        }
        
        $new = $this->withBody(new Stream(fopen('php://temp', 'r+')));
        $new->getBody()->write($json);
        //$new = $new->withHeader('Content-Type', 'application/json;charset=utf-8');        
        $new = $new->withHeader('Content-Type', 'application/json');        

        if (isset($status)) {
        	$new = $new->withStatus($status);        
        }
		
        return $new;
    }
}
