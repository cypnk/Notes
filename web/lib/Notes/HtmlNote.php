<?php declare( strict_types = 1 );

namespace Notes;

class HtmlNote extends Controllable {
	
	public readonly \DOMDocument $dom;
	
	public function __construct( \Notes\Controller $ctrl, string $html ) {
		parent::__construct( $ctrl );
		
		$this->dom		= new \DOMDocument();
		$this->dom->loadHTML( 
			$html, 
			\LIBXML_HTML_NODEFDTD | \LIBXML_NOERROR | 
			\LIBXML_NOWARNING | \LIBXML_NOXMLDECL | 
			\LIBXML_COMPACT | \LIBXML_NOCDATA | \LIBXML_NONET
		);
	}
	
	public function render() : string {
		$temp	= new \DOMDocument();
		$body	= 
		$this->dom->getElementsByTagName('body')->item(0);
		foreach ( $body->childNodes as $child ){
   			$temp->appendChild( $temp->importNode( $child, true ) );
  		}
		
		return $temp->saveHTML();
	}
}

