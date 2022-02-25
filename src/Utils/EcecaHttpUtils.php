<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Security\Http\HttpUtils;

class EcecaHttpUtils extends HttpUtils{
	
	/**
	 * Creates a Request.
	 *
	 * @param Request $request The current Request instance
	 * @param string  $path    A path (an absolute path (/foo), an absolute URL (http://...), or a route name (foo))
	 *
	 * @return Request A Request instance
	 */
	public function createRequest(Request $request, $path)
	{
		$newRequest = parent::createRequest($request, $path);
		$newRequest->headers = $request->headers;
			
		return $newRequest;
	}
}