<?php declare( strict_types = 1 );

namespace Notes;

class Log extends Content {
	
	public function __set( $name, $value ) {
		switch ( $name ) {
			case 'label':
				$this->_content['label'] = 
					( string ) $value;
				return;
				
			case 'body':
				$this->_content['body'] = 
					( string ) $value;
				return;
		}
		parent::__set( $name, $value );
	}
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'label':
				return $this->_content['label'] ?? '';
			
			case 'body'
				return $this->_content['body'] ?? '';
		}
		
		return parent::__get( $name );
	}
	
	public function save() : bool {
		if ( isset( $this->id ) ) {
			$this->error( 'Attempted edit, logs are read-only' );
			return false;
		}
		
		if ( empty( $this->_content['label'] ) ) {
			$this->error( 'Attempted log save without setting label' );
			return false;
		}
		
		// Set default data
		$this->_content['body']		??= '';
		$this->_content['session_id']	??= \session_id();
		
		// Request parameters
		$req	= $this->controller->getConfig()->getRequest();
		$this->_content['ip']		??= $req->getIP();
		$this->_content['host']		??= $req->getHost();
		$this->_content['secure']	??= $req->isSecure();
		$this->_content['method']	??= $req->getMethod();
		$this->_content['uri']		??= $req->getURI();
		$this->_content['query_string']	??= $req->getQS();
		$this->_content['user_agent']	??= $req->getUA();
		$this->_content['language']	??= $req->getLang();
		$this->_content['file_range']	??= $req->getFileRange();
		
		$db = $this->getData();
		$id = 
		$db->setInsert(
			"INESRT INTO event_logs ( content, status ) 
				VALUES ( :content, :status );",
			[
				':content'	=> \Notes\Util::encode( $this->_content ),
				':status'	=> $this->status ?? 0
			],
			\LOGS
		);
		
		if ( empty( $id ) ) {
			return false;
		}
		$this->id = $id;
		return true;
	}
}


