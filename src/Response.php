<?php
declare(strict_types=1);

namespace Comet;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

class Response extends GuzzleResponse implements ResponseInterface
{
    public function __construct(
        $status = 200,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    ) {
		parent::__construct($status, $headers, $body, $version, $reason);    
    }
}
