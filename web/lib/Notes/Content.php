<?php declare( strict_types = 1 );

namespace Notes;

abstract class Content extends Entity {
	
	public string $body;
	
	public int $sort_order;
	
	protected int $_lang_id;
	
	protected int $_parent_id;
	
	protected array $_authorship	= [];
	
	protected array $_content	= [];
	
	public function setBody( string $_body ) {
		$this->_content['body'] = $_body;
	}
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'parent_id':
				$this->_parent_id = ( int ) $value;
				return;
				
			case 'lang_id':
				$this->_lang_id = ( int ) $value;
				return;
				
			case 'authorship':
				$this->_authorship = 
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
			case 'parent_id':
				return $this->_parent_id ?? 0;
				
			case 'lang_id':
				return $this->_lang_id ?? 0;
				
			case 'content':
				return $this->_content;
			
			case 'authorship':
				return $this->_authorship;
			
			case 'content_str':
				return \Notes\Util::encode( $this->_content );
			
			case 'body':
				return $this->_content['body'] ?? '';
		}
		
		return parent::__get( $name );
	}
	
	public function __toString() {
		return 
		\Notes\Util::encode( 
			[ ...new ArrayIterator( [ 
				"id"		=> $this->id, 
				"created"	=> $this->created,
				"updated"	=> $this->updated,
				"setting_id"	=> $this->setting_id,
				"lang_id"	=> $this->lang_id ?? 0,
				"parent_id"	=> $this->parent_id ?? 0,
				"sort_order"	=> $this->sort_order ?? 0,
				"content"	=> $this->_content,
				"authorship"	=> $this->_authorship
			] ), ...$this->_settings ] 
		);
	}
}

