<?php declare( strict_types = 1 );

namespace Notes;

abstract class UserAuthForm extends Form {
	
	/**
	 *  Common username property
	 *  @var string
	 */
	public string $username;
	
	/**
	 *  Current form label in database
	 *  @param string
	 */
	protected string $form_name	= 'web_user_auth';

	public function render() : string {
		// Generate HTML output
		if ( !isset( $this->rendered ) ) {
			$this->rendered = 
			$this->form_type->render( 
				$this->controller, $this->params['form'] 
			)
		}
		
		// Generate event placeholder content by current form name
		$this->controller->run( 
			static::$form_name, [ 'form' => $this ] 
		);
		
		// Generate anti-XSRF tokens
		$pair	= 
		$this->genNoncePair( 
			$this->getControllerParam( '\\Notes\\Config' ),
			static::$form_name
		);
		
		// Return with placeholders replaced
		// TODO: Load language definitions from databse
		return 
		\strtr( $this->rendered, [
			...new ArrayIterator( [ 
				'{username}'	=> $this->username ?? '',
				'{token}'	=> $pair['token'],
				'{nonce}'	=> $pair['nonce']
			] ), 
			...$this->controller->output( static::$form_name )
		] );
	}
}
