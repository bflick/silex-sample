<?php

namespace CorsHelper\RequestMatcher;

use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @deprecated Use \CultuurNet\UDB3\HttpFoundation\RequestMatcher\PreflightRequestMatcher instead.
 * @see https://github.com/cultuurnet/udb3-http-foundation/blob/master/src/RequestMatcher/PreflightRequestMatcher.php
 */
class PreflightRequestMatcher implements RequestMatcherInterface
{
    public function matches(Request $request)
    {
        return $this->isPreflightRequest($request);
    }
    /**
     * This will match any CORS preflight requests.
     * Borrowed form the silex-cors-provider.
     *
     * @param Request $request
     * @return bool
     */
    private function isPreflightRequest(Request $request)
    {
        return $request->getMethod() === "OPTIONS" && $request->headers->has("Access-Control-Request-Method");
    }
}
