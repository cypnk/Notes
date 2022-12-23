<?php declare( strict_types = 1 );

namespace Notes;

class Message {
	
	/**
	 *  Message source or destination URI
	 *  @var string
	 */
	protected readonly string $uri;
	
	/**
	 *  Message protocol. E.G. HTTP 1.1
	 *  @var string
	 */
	protected readonly string $protocol;
	
	/**
	 *  Current message querystring or path attachment
	 *  @var string
	 */
	protected readonly string $querystring;
	
	/**
	 *  Core settings and configuration 
	 *  @var \Notes\Config
	 */
	protected readonly object $config;
	
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl );
		
		$ctrl->addParam( '\\\Notes\\Config' );
		$this->config	= $ctrl->getParam( '\\\Notes\\Config' );
	}
	
	/**
	 *  Get or guess current server protocol
	 *  
	 *  @param string	$assume		Default protocol to assume if not given
	 *  @return string
	 */
	public function getProtocol( string $assume = 'HTTP/1.1' ) : string {
		if ( isset( $this->protocol ) ) {
			return $this->protocol;
		}
		$this->protocol = $_SERVER['SERVER_PROTOCOL'] ?? $assume;
		return $this->protocol;
	}
}


