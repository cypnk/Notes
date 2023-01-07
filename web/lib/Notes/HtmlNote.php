<?php declare( strict_types = 1 );

namespace Notes;

class HtmlNote extends Controllable {
	
	public readonly \DOMDocument $dom;
	
	/**
	 *  Build an HTML segment note
	 *  
	 *  @param \Notes\Controller	$ctrl		Current event controller
	 */
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl );
		
		$this->dom	= new \DOMDocument();
	}
	
	/**
	 *  Extract note body
	 *  
	 *  @return string
	 */
	public function render() : string {
		if ( empty( $this->dom->childNodes ) ) {
			return '';
		}
		
		$temp	= new \DOMDocument();
		foreach ( $this->dom->childNodes as $child ) {
   			$temp->appendChild( $temp->importNode( $child, true ) );
  		}
		
		return $temp->saveHTML();
	}
}

