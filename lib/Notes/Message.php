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
	 *  @var \PubCabin\Config
	 */
	protected readonly object $config;
	
	/**
	 *  Main event controller
	 *  @var \PubCabin\Controller
	 */
	protected readonly object $ctrl;
	
	public function __construct( Controller $ctrl ) {
		$this->ctrl	= $ctrl;
		$this->config	= $ctrl->getConfig();
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


