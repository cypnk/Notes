<?php declare( strict_types = 1 );

namespace Notes;

abstract class Content extends Entity {
	
	public readonly string $body;
	
	public int $sort_order;
	
	public int $lang_id;
	
	public int $parent_id;
	
	protected array $_authorsihp	= [];
	
	protected array $_content	= [];
	
	protected string $_content_str;
	
	protected array	$params		= [];
	
	public abstract function __construct( ) {}
	
	public function setBody( string $_body ) {
		$this->_content['body'] = $body;
	}
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'authorship':
				$this->authorship = 
					static::formatSettings( $value );
				return;
				
			case 'content':
				$this->_content = 
					static::formatSettings( $value );
				return;
				
			case 'sort_order':
				$this->_sort_order = ( int ) $value;
				return;
		}
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch( $name ) {
			case 'content':
				return $this->_content ?? [];
			
			case 'body':
				return $this->_content['body'] ?? '';
		}
		
		return parent::__get( $name );
	}
}

