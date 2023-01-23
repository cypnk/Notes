<?php declare( strict_types = 1 );
/**
 *  @file	/web/lib/Notes/AtomFeed.php
 *  @brief	Available application feature discovery document
 *  @link	https://www.ietf.org/rfc/rfc4287.txt
 */

namespace Notes;

class AtomFeed extends AtomBase {
	
	/**
	 *  Main sydnication feed
	 *  @var \DOMElement
	 */
	protected readonly \DOMElement $feed;
	
	public function __construct( \Notes\Controller $ctrl ) {
		parent::__construct( $ctrl );
		
		$this->feed = 
		$this->addNode( 'feed', '', 'atom', true );
	}
	
	/**
	 *  TODO: Add entries, etc...
	 */
}

