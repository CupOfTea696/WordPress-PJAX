<?php

namespace CupOfTea\WordPress\Pjax;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderBag;

class Pjax
{
    /**
     * The DomCrawler instance.
     *
     * @var \Symfony\Component\DomCrawler\Crawler
     */
    protected $crawler;
    
    protected $request;
    
    public function __construct()
    {
        $this->request = Request::createFromGlobals();
        
        if ($this->isPjax() && ! is_admin()) {
            add_action('init', [$this, 'captureResponse']);
            add_action('shutdown', [$this, 'sendResponse'], 0);
        }
    }
    
    public function captureResponse()
    {
        ob_start([$this, 'handle']);
    }
    
    public function sendResponse()
    {
        ob_end_flush();
    }
    
    /**
     * Handle the final output.
     *
     * @param  string  $response
     * @return mixed
     */
    public function handle($response)
    {
        $request = $this->request;
        $response = Response::create($response);
        
        $this->filterResponse($response, $request->headers->get('X-PJAX-CONTAINER'))
            ->setUriHeader($response, $request)
            ->setVersionHeader($response, $request);
        
        $response->sendHeaders();
        
        return $response->getContent();
    }
    
    /**
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $container
     * @return $this
     */
    protected function filterResponse(Response $response, $container)
    {
        $crawler = $this->getCrawler($response);
        
        $response->setContent(
            $this->makeTitle($crawler).
            $this->fetchContainer($crawler, $container)
        );
        
        return $this;
    }
    
    /**
     * @param  \Symfony\Component\DomCrawler\Crawler  $crawler
     * @return null|string
     */
    protected function makeTitle(Crawler $crawler)
    {
        $pageTitle = $crawler->filter('head > title');
        
        if (! $pageTitle->count()) {
            return;
        }
        
        return "<title>{$pageTitle->html()}</title>";
    }
    
    /**
     * @param  \Symfony\Component\DomCrawler\Crawler  $crawler
     * @param  string  $container
     *
     * @return string
     */
    protected function fetchContainer(Crawler $crawler, $container)
    {
        $content = $crawler->filter($container);
        
        if (! $content->count()) {
            http_response_code(422);
            die();
        }
        
        return $content->html();
    }
    
    /**
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  \Symfony\Component\HttpFoundation\Request   $request
     * @return $this
     */
    protected function setUriHeader(Response $response, Request $request)
    {
        $response->headers->set('X-PJAX-URL', $request->getRequestUri());
        
        return $this;
    }
    
    /**
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  \Symfony\Component\HttpFoundation\Request   $request
     * @return $this
     */
    protected function setVersionHeader(Response $response, Request $request)
    {
        $crawler = $this->getCrawler($this->createReponseWithLowerCaseContent($response));
        $node = $crawler->filter('head > meta[http-equiv="x-pjax-version"]');
        
        if ($node->count()) {
            $response->headers->set('X-PJAX-VERSION', $node->attr('content'));
        }
        
        return $this;
    }
    
    protected function getCrawler(Response $response)
    {
        if (! $this->crawler) {
            $this->crawler = new Crawler($response->getContent());
        }
        
        return $this->crawler;
    }
    
    protected function isPjax()
    {
        return $this->request->headers->get('X-PJAX') == true;
    }
    
    /**
     * Make the content of the given response lowercase.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function createReponseWithLowerCaseContent(Response $response)
    {
        $lowercaseContent = strtolower($response->getContent());
        
        return Response::create($lowercaseContent);
    }
}
