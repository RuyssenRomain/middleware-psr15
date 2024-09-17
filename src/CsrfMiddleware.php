<?php

namespace Grafikart\Csrf;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use TypeError;

    class CsrfMiddleware  implements MiddlewareInterface {

        private $session;
        private $sessionKey;    
        private $formKey;    
     
        public function __construct(&$session = [], string $sessionKey = 'csrf.token', string $formKey = '_csrf')
        {   
            $this->testSession($session);
            $this->session = $session;
            $this->sessionKey = $sessionKey;
            $this->formKey = $formKey;
        }
       
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
        {
          if (in_array($request->getMethod(), ['PUT', 'POST', 'DELETE'])) {
                $params = $request->getParseBody()?: [];
                if (!array_key_exists($this->formKey, $params)) {

                    throw new NoCsrfException();
                }
                
                if (!in_array($params[$this->formKey], $this->session [$this->sessionKey] ?? []))
                 {
                    return $handler->handle($request);
                }
                    throw new InvalidCsrfException();            
            }
            return $handler->handle($request);           
        }

        
        public function generateToken(): string {
            // Génération d'un token CSRF (exemple simple)
            $token = bin2hex(random_bytes(32));
            $token = $this->session [$this->sessionKey] ?? [];
            $tokens[] = $token ;
            $this->session[$this->sessionKey] = $tokens;
            return $token;
        }


        private function testSession($session):void  {

            if(!is_array($session) && !$session instanceof \ArrayAccess){
                throw new TypeError('session is not an array');
            }
        }