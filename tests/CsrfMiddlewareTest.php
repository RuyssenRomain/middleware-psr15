<?php
namespace Grafikart\Csrf\test;

use Grafikart\Csrf\CsrfMiddleware;
use Grafikart\Csrf\InvalidCsrfException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfMiddlewareTest extends TestCase {

    private function makeMiddleware(&$session = []){
        return new CsrfMiddleware($session);
    }

    public function makeRequest(string $method = 'GET', ?array $params = null)
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
        $request->method('getMethod')->willReturn($method);
        $request->method('getParseBody')->willReturn($params);  // Correction ici
        return $request;
    }

    public function testAcceptValidSession () {
        $a = [];
        $b = $this->getMockBuilder(\ArrayAccess::class)->getMock();
        $middlewareA = new CsrfMiddleware($a);
        $middlewareB = new CsrfMiddleware($b);
        $this->assertInstanceOf(CsrfMiddleware::class, $middlewareA);
        $this->assertInstanceOf(CsrfMiddleware::class, $middlewareB);     
    }

    public function testAcceptInValidSession () {
       $this->expectException(\TypeError::class);
        $middlewareA = new CsrfMiddleware(new \stdClass());
        $middlewareB = new CsrfMiddleware($b);

        $this->assertInstanceOf(CsrfMiddleware::class, $middlewareA);
        $this->assertInstanceOf(CsrfMiddleware::class, $middlewareB);     
    }

    public function testRejectInvalidSession () {
        $this->expectException(\TypeError::class);
        $a = new \stdClass();
        $middlewareA = $this->makeMiddleware($a);
    }

    private function makeResponse()
    {
        return $this->getMockBuilder(ResponseInterface::class)  // Utilisation correcte ici
            ->getMock();
    }

    public function MakeHandle()
    {
        $handle = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $handle->method('handle')->willReturn($this->makeResponse());
        return $handle;
    }

    public function testGetPass()
    {
        $middleware = $this->makeMiddleware();
        $handle = $this->MakeHandle();
        $handle->expects($this->once())->method('handle');
        $middleware->process(
            $this->makeRequest('GET'),
            $handle
        );
    }

    public function testPreventPost()
    {
        $middleware = $this->makeMiddleware();
        $handle = $this->MakeHandle();
        $handle->expects($this->never())->method('handle');
        $this->expectException(NoCsrfException::class);
        $middleware->process(
            $this->makeRequest('POST'),
            $handle
        );
    }

    public function testPostsuccessValidToken()
    {
        $middleware = $this->makeMiddleware();
        $token = $middleware->generateToken();
        $handle = $this->MakeHandle();
        $handle->expects($this->once())->method('handle')->willReturn($this->makeResponse());
        $middleware->process(
            $this->makeRequest('POST', ['csrf' => $token]), 
            $handle
        );
    }

    public function testPostInvalidToken()
    {
        $middleware = $this->makeMiddleware();
        $token = $middleware->generateToken();
        $handle = $this->MakeHandle();
        $handle->expects($this->never())->method('handle');
        $this->expectException(InvalidCsrfException::class);
        $middleware->process(
            $this->makeRequest('POST', ['csrf' => 'invalid_token']),  // Token invalide
            $handle
        );
    }
}
