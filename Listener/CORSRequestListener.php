<?php

namespace Soil\CORSRequestHandlerBundle\Listener;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class CORSRequestListener {

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var Request
     */
    protected $masterRequest;

    /**
     * @var array
     */
    protected $eligibleRoutes;

    public function __construct($requestStack, $eligibleRoutes) {
        $this->requestStack = $requestStack;
        $this->eligibleRoutes = $eligibleRoutes;
    }


    public function onRequest(GetResponseEvent $e) {
        if (!$this->checkRequest()) return;

        $request = $e->getRequest();

        if ($request->isMethod('OPTIONS'))  {
            $response = $this->handleOptionsRequest($request);

            $e->setResponse($response);
            return;
        }
    }

    protected function handleOptionsRequest(Request $request)   {
        $headersBack = implode(', ', $request->headers->keys());

        $response = new Response();
        $response->headers->add([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT',
            'Content-Type' => 'text/html; charset=utf-8',
            'Access-Control-Max-Age' => 10,
            'Access-Control-Allow-Headers' => $headersBack
        ]);

        return $response;
    }

    public function onResponse(FilterResponseEvent $e)  {
        if (!$this->checkRequest()) return;

        $uri = $e->getRequest()->getRequestUri();

        $e->getResponse()->headers->add(['Access-Control-Allow-Origin' => '*']);

    }

    protected function getMasterRequest()   {
        if (!$this->masterRequest)  {
            $this->masterRequest = $this->requestStack->getMasterRequest();
        }

        return $this->masterRequest;
    }

    protected function checkRequest()   {
        $masterRequest = $this->getMasterRequest();

        if ($masterRequest) {
            $route = $masterRequest->attributes->get('_route');

            if (in_array($route, $this->eligibleRoutes))    {
                return true;
            }
            else    {
                return false;
            }

        }
        else    {
            return false;
        }
    }
}