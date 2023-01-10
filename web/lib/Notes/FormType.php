<?php declare( strict_types = 1 );

namespace Notes;

enum FormType {
	
	case Validated;		// Should include nonce/token pairs
	case UnValidated;
	
	/**
	 *  Render current form in HTML with given inputs
	 */
	public function render(
		\Notes\Controller	$ctrl,
		array			$content
	) : string {
		// Ensure inputs
		$form['inputs']	??= [];
		
		// Ensure extras
		$form['extras']	??= [];
		
		// Prepare placeholders
		$data	= \Notes\Util::placeholders( $content );
		
		// Default placeholders
		static::baseDefaults( $data );
		
		// Build note
		$note	= \Notes\HtmlNote( $ctrl );
		
		// Pre-form event content placeholder
		$note->dom->appendChild(
			$note->dom->createTextNode( '{form_before}' )
		);
		
		// Run placeholder event
		$ctrl->run( 'form_before', $data );
		$data	= [ 
			...$data, 
			...$ctrl->output( 'form_before' ) 
		];
		
		// Base element and common attributes
		$form = $note->dom->createElement( 'form' );
		$form->setAttribute( 'id', $data['{id}'] );
		$form->setAttribute( 'name', $data['{name}'] );
		$form->setAttribute( 'class', $data['{form_classes}'] );
		$form->setAttribute( 'action', $data['{action}'] );
		$form->setAttribute( 'enctype', $data['{enctype}'] );
		$form->setAttribute( 'method', $data['{method}'] );
		
		// Add tokens if this is a validation type 
		$this->addTokens( $form, $data );
		
		// Any extra parameters
		$this->addExtras( $form, $content );
		
		// Append legend, if given
		$this->addLegend( $form, $data );
		
		// Append inputs
		foreach( $content['inputs'] as $input ) {
			static::buildInputs( $ctrl, $form, $input );
		}
		
		// "More" content
		$form->appendChild(
			$note->dom->createTextNode( '{more}' )
		);
		
		// Add to note
		$note->dom->appendChild( $form );
		
		// Post-form event content placeholder
		$note->dom->appendChild(
			$note->dom->createTextNode( '{form_after}' )
		);
		$ctrl->run( 'form_after', $data );
		$data	= [ 
			...$data, 
			...$ctrl->output( 'form_after' ) 
		];
		
		// Send back with remaining placeholders replaced
		return \strtr( $note->render(), $data );
	}
	
	/**
	 *  Add form validation hidden fields or set unvalidated marker
	 *  
	 *  @param \DOMElement		$form		Current form element
	 *  @param array		$data		Placeholder content
	 */
	protected function addTokens( \DOMElement $form, array $data ) {
		match( $this ) {
			// Validated forms get a nonce and token
			FormType::Validalidated	=> ( function() use ( $form ) {
				$token = \Notes\InputType::Hidden;
				$form->appendChild( 
					$token->buildInput( $form, [ 
						'name'	=> 'token',
						'value'	=> $data['{token}']
					] ) 
				);
				$nonce = \Notes\InputType::Hidden;
				$form->appendChild( 
					$nonce->buildInput( $form, [ 
						'name'	=> 'nonce',
						'value'	=> $data['{nonce}']
					] ) 
				);
			} )(),
			
			// Unvalidated forms get a unique identifier with generated date
			FormType::Unvalidated	=> ( function() use ( $form ) {
				$id = \Notes\InputType::Hidden;
				$form->appendChild( 
					$id->buildInput( $form, [ 
						'name'	=> 'identifier',
						'value'	=> 
						\base64_encode( 
							\Notes\Util::genId() . '|' . 
							\Notes\Util::utc()
						)
					] ) 
				);
			} )()
		};
	}
	
	public static function addExtras( \DOMElement $el, array $content ) {
		// Nothing to add?
		if ( 
			empty( $content['extras'] )	&& 
			empty( $content['extra'] )
		) {
			return;
		}
		
		// Apply single extra parameter
		if ( !\is_array( $content['extra'] ) ) {
			$v = ( string ) $content['extra'];
			$el->setAttribute( $v, $v );
		}
		
		if  ( !\is_array( $content['extras'] ) ) {
			return;
		}
		
		// Apply extra group
		foreach( $content['extras'] as $i => $j ) {
			// Add sub elements if nested
			if ( \is_array( $j ) ) {
				foreach ( $j as $k = $v ) {
					$el->setAttribute( $k, $v );
				}
				continue;
			}
			
			// Set element to be itself
			$el->setAttribute( 
				$i, empty( $j ) ? $i : $j 
			);
		}
	}
	
	/**
	 *  Set default placeholder values
	 */
	public static baseDefaults( array &$data ) {
		// Required
		$data['{id}']			??= '';
		$data['{legend}']		??= '';
		
		// Operation
		$data['{action}']		??= '/';
		$data['{enctype}']		??= '';
		$data['{method}']		??= 'get';
		
		// Validation
		$data['{token}']		??= '';
		$data['{nonce}']		??= '';
		
		// Event content
		$data['{form_after}'		??= '';
		$data['{form_before}'		??= '';
		$data['{legend_after}'		??= '';
		$data['{legend_before}'		??= '';
		$data['{more}']			??= '';
		
		// Default styling
		$data['{form_classes}']		??= 'pa4 black-80 measure';
		$data['{legend_classes}']	??= 'mb2 f4 measure';
		
		// Filters
		$data['{method}']		= 
		static::cleanFormMethodType( $data['{method}'] );
		
		$data['{enctype}']		= 
		static::cleanFormEnctype( $data['{enctype}'] );
	}
	
	/**
	 *  Filter form sending method type, defaults to get or post
	 *  
	 *  @param string	$v	Raw form method
	 *  @return string
	 */
	public static function cleanFormMethodType( string $v ) : string {
		static $fm = [ 'get', 'post' ];
		
		$v = \strtolower( trim( $v ) );
		return \in_array( $v, $fm ) ? $v : 'get';
	}
	
	/**
	 *  Form encoding type helper, defaults to 'application/x-www-form-urlencoded'
	 *  
	 *  @param string	$v	Raw encoding type
	 *  @return string
	 */
	public static function cleanFormEnctype( string $v ) : string {
		static $ec = 	=  [
			'application/x-www-form-urlencoded',
			'multipart/form-data',
			'text/plain'
		];
		
		$v = \strtolower( trim( $v ) );
		return \in_array( $v, $ec ) ?
			$v : 'application/x-www-form-urlencoded';
	}
	
	/**
	 *  Append a form legend if that content is available
	 */
	public function addLegend( 
		\DOMElement	$form, 
		array		$data
	) {
		if ( empty( $data['{legend}'] ) ) {
			return;
		}
		
		// Pre-legend event content placeholder
		$form->appendChild(
			$form->ownerDocument->createTextNode( 
				'{legend_before}' 
			)
		);
		
		$legend = 
		$form->ownerDocument->createElement( 'legend', $data['legend'] );
		
		$legend->setAttribute( 'class', $data['{legend_classes}'] );
		
		$form->appendChild( $legend );
		
		// Post-legend event content placeholder
		$form->appendChild(
			$form->ownerDocument->createTextNode( 
				'{legend_after}' 
			)
		);
	}
	
	public static function buildInputs( \DOMElement $form, array $input ) {
		$input['type'] ??= '';
		
		$out = 
		match( \strtolower( $input['type'] ) ) {
			'text'		=> \Notes\InputType::Text,
			'email'		=> \Notes\InputType::Email,
			'radio'		=> \Notes\InputType::Radio,
			'search'	=> \Notes\InputType::Search,
			'select'	=> \Notes\InputType::Select,
			'checkbox'	=> \Notes\InputType::Checkbox,
			'password'	=> \Notes\InputType::Password,
			'textarea'	=> \Notes\InputType::Textarea,
			'wysiwyg'	=> \Notes\InputType::Wysiwyg,
			
			'date', 'datetime', 'datetime-local'
					=> \Notes\InputType::Datetime,
			
			'list', 'datalist', 'autofilldata'
					=> \Notes\InputType::Datalist,
					
			'hidden'	=> \Notes\InputType::Hidden,
			'button'	=> \Notes\InputType::Button,
			'reset'		=> \Notes\InputType::Reset,
			'submit'	=> \Notes\InputType::Submit,
			
			// TODO: Calendar, Upload, Range
			default		=> \Notes\InputType::Other
		}
		
		$form->appendChild( $out->buildInput( $form, $input ) );
	}
}

	

