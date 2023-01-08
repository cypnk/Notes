<?php declare( strict_types = 1 );

namespace Notes;

enum InputType {
	
	case Text;
	case Email;
	case Radio;
	case Select;
	case Search;
	case Wysiwyg;
	case Checkbox;
	case Password;
	case Textarea;
	case DateTime;
	
	case Hidden;
	case Other;
	
	case Submit;
	case Reset;
	case Button;
	
	// TODO
	case Calendar;
	case Upload;
	case Range;
	
	/**
	 *  Render the current input type in the above templates
	 */
	public function buildInput( \DOMElement $form, array $input ) {
		// Default to text type if not given
		$input['type'] ??= 'text';
		$input['type'] = \strtolower( $input['type'] );
		
		$input['value'] ??= '';
		
		$data = \Notes\Util::placeholders( $input );
		
		// Default settings
		static::baseDefaults( $data );
		
		// Default styling
		static::baseStyling( $data );
		
		$node	= $this->buildNode( $form, $input, $data );
		$node->setAttribute( 'id', $data['{id}'] );
		$node->setAttribute( 'name', $data['{name}'] );
		
		return 
		match( $this ) {
			// Send bare for inline types
			InputType::Submit,
			InputType::Button,
			InputType::Hidden	=> $node,
			
			// Wrap everything else
			default			=>
			$this->wrapInput( $node, $form, $data )
		}
	}
	
	protected function buildNode( \DOMElement $form, array $input, array $data ) {
		$node = 
		match( $this ) {
			// Flat text
			InputType::Text, 
			InputType::Email,
			InputType::Search, 
			InputType::Password	=> ( function() use ( $form, $data ) {
				$e = $form->ownerDocument->createElement( 'input' );
				$e->setAttribute( 'type', $data['{type}'] );
				$e->setAttribute( 'value', $data['{value}'] );
				$e->setAttribute( 'placeholder', $data['{placeholder}'] );
				
				// HTML5 validation pattern
				if ( !empty( $data['{pattern}'] ) ) {
					$e->setAttribute( 'pattern', $data['{pattern}'] );
				}
				
				return $e;
			} )(),
			
			// Datetime
			InputType::DateTime	=> ( function() use ( $form, $data ) {
				$e = $form->ownerDocument->createElement( 'input' );
				
				// Override type
				$e->setAttribute( 'type', 'datetime-local' );
				$e->setAttribute( 'value', $data['{value}'] );
				$e->setAttribute( 'placeholder', $data['{placeholder}'] );
				
				return $e;
			} )(),
			
			// Select
			InputType::Select	=> ( function() use ( $form, $data, $input ) {
				$e = $form->ownerDocument->createElement( 'select' );
				
				// Set unselected (empty) option
				if ( !empty( $data['{unselect_option}'] ) ) {
					$u = $form->ownerDocument->createElement( 'option', '--' );
					$e->appendChild( $u );
				} 
				$this->addOptions( $e, $form, $input['options'] ?? [] );
				return $e;
			} )(),
			
			// Option
			InputType::Radio,
			InputType::Checkbox	=> ( function() use ( $form, $data, $input ) {
				$e = $form->ownerDocument->createElement( 'input' );
				$e->setAttribute( 'type', $input['type'] );
				$e->setAttribute( 'value', $input['value'] );
				return $e;
			} )(),
			
			// Multiline, formatted start with a textarea
			InputType::Textarea,
			InputType::Wysiwyg	=> ( function() use ( $form, $data, $input ) { 
				$e = $form->ownerDocument->createElement( 
					'textarea', $input['{value}']
				);
				
				$e->setAttribute( 'rows', $input['rows'] ?? 5 );
				$e->setAttribute( 'cols', $input['cols'] ?? 50 );
				$e->setAttribute( 'placeholder', $data['{placeholder}'] );
				return $e;
			} )(),
			
			// Hidden inputs
			InputType::Hidden	=> ( function() use ( $form, $data, $input ) {
				$e = $form->ownerDocument->createElement( 'input' );
				$e->setAttribute( 'type', 'hidden' );
				$e->setAttribute( 'value', $data['{value}'] );
				return $e;
			} )(),
			
			// Buttons
			InputType::Button,
			InputType::Reset,
			InputType::Submit	=> ( function() use ( $form, $data, $input ) {
				$e = $form->ownerDocument->createElement( 'input' );
				$e->setAttribute( 'value', $data['{value}'] );
				$e->setAttribute( 'type', $input['type'] );
				
				return $e;
			} )(), 
			
			// Default input type
			default		=>  ( function() use ( $form, $input ) {
				$e = $form->ownerDocument->createElement( 'input' );
				$e->setAttribute( 'type', $data['{type}'] );
				$e->setAttribute( 'value', $data['{value}'] );
				return $e;
			} )()
		};
		
		$class = 
		match( $input['type'] ) {
			'hidden'	=> '',
			'submit'	=> $data['{submit_classes}'],
			'reset'		=> $data['{reset_classes}'],
			default		=> $data['{input_classes}']
		};
		$e->setAttribute( 'class', $class );
		
		$node->setAttribute( 'id', $data['{id}'] );
		$node->setAttribute( 'name', $data['{name}'] );
		
		// Has a description?
		if ( !empty( $data['{desc}'] ) ) {
			$node->setAttribute( 
				'aria-described-by', 
				$data['{id}'] . '-desc' 
			);
		}
		
		// Allow multiple
		if ( !empty( $data['{multiple}'] ) ) {
			$e->setAttribute( 'multiple', 'multiple' );
		}
				
		return $node;
	}
	
	// TODO
	public function validate( array $input ) {
		
	}
	
	/**
	 *  Form field wrapper
	 */
	protected function wrapInput( \DOMElement $node, \DOMElement $form, array $data ) {
		$wrap = $form->ownderDocument->createElement( 'p' );
		$wrap->setAttribute( 'class', $data['{input_wrap_classes}'] );
		
		$this->addLabel( $wrap, $node, $form, $data );
		
		// Pre-input event content
		$wrap->appendChild(
			$form->ownerDocument->createTextNode( 
				'{input_field_before}' 
			)
		);
		
		match( $this ) {
			// Wysiwyg needs an extra element inside the wrap
			InputType::Wysiwyg	=> ( function() use ( $form, $node ) { 
				$w = $form->ownderDocument->createElement( 'div', '' );
				$w->setAttribute( 'rel', $node->getAttribute( 'id' ) . '-wysiwyg' );
				$w->setAttribute( 'class', '{wysiwyg_classes}' );
				$wrap->appendChild( $w );
				$wrap->appendChild( $node );
			} )(),
			
			InputType::Radio,
			InputType::Checkbox	=> null,	// Already added
			
			default => $wrap->appendChild( $node )
		};
		
		// Append validation message holder, if needed
		$this->addMessages( $wrap, $form, $data );
		
		// Post-input event content
		$wrap->appendChild(
			$form->ownerDocument->createTextNode( 
				'{input_field_after}' 
			)
		);
		
		// Add description if given
		$this->addDescription( $wrap, $form, $data );
		
		return $wrap;
	}
	
	/**
	 *  Brief input description
	 */
	protected function addLabel( 
		\DOMElement	$wrap, 
		\DOMElement	$node, 
		\DOMElement	$form, 
		array		$data 
	) {
		if ( empty( $data['{label}'] ) ) {
			return;
		}
		
		// Pre-label event content
		$wrap->appendChild(
			$form->ownerDocument->createTextNode( 
				'{label_before}' 
			)
		);
		
		$label = $form->ownderDocument->createElement( 'label', $data['{label}'] );
		$label->setAttribute( 'for', $data['{id}'] );
		$label->setAttribute( 'class', $data['{label_classes}'] );
		
		$this->addSpecial( $label, $form, $data );
		
		match( $this ) {
			// Options handled differently
			InputType::Radio,
			InputType::Checkbox	=> ( function() use ( $label, $wrap, $node ) {
				$label->appendChild( $node );
				$wrap->appendChild( $label );
			} )(),
			
			// Add input label
			default			=> $wrap->appendChild( $label )
		};
		
		// Post-label event content
		$wrap->appendChild(
			$form->ownerDocument->createTextNode( 
				'{label_after}' 
			)
		);
	}
	
	/**
	 *  Special validation parameters E.G. "required" or "optional"
	 */
	protected function addSpecial( \DOMElement $label, \DOMElement $form, array $data ) {
		if ( empty( $data['{special}'] ) ) {
			return;
		}
		// Pre-special event content
		$label->appendChild(
			$form->ownerDocument->createTextNode( 
				'{special_before}' 
			)
		);
		
		$special = $form->ownderDocument->createElement( 'span', $data['{special}'] );
		$special->setAttribute( 'class', $data['{special_classes}'] );
		
		$label->appendChild( $special );
		
		// Post-special event content
		$label->appendChild(
			$form->ownerDocument->createTextNode( 
				'{special_after}' 
			)
		);
	}
	
	/**
	 *  Input content instructions
	 */
	protected function addDescription( \DOMElement $wrap, \DOMElement $form, array $data ) {
		if ( empty( $data['{desc}'] ) ) {
			return;
		}
		// Pre-descriptipon event content
		$wrap->appendChild(
			$form->ownerDocument->createTextNode( 
				'{desc_before}' 
			)
		);
		
		$desc = $form->ownderDocument->createElement( 'small', $data['{desc}'] );
		$label->setAttribute( 'id', $data['{id}'] . '-desc' );
		$label->setAttribute( 'class', $data['{desc_classes}'] );
		$wrap->appendChild( $desc );
		
		// Post-descriptipon event content
		$wrap->appendChild(
			$form->ownerDocument->createTextNode( 
				'{desc_after}' 
			)
		);
	}
	
	/**
	 *  Error/validity pseudo element message holder
	 *   
	 *  @param 
	 *  @example
	 *  input:valid ~ .input-message { display:none; }
	 *  input:required:invalid ~ .input-message::after { content: attr(data-required); }
	 *  input:invalid:not(:placeholder-shown) ~ .input-message::after { content: attr(data-validation); }
	 */
	public function addMessages(
		\DOMElement	$wrap, 
		\DOMElement	$form, 
		array		$data 
	) {
		if ( empty( $data['{messages}'] ) ) {
			return;
		}
		$msg = 
		$form->ownerDocument->createElement( 'span', $data['{messages}'] );
		
		$msg->setAttribute( 'class', $data['{message_classes}'] );
		$wrap->appendChild( $msg );
	}
	
	/**
	 *  Set default placeholder values
	 */
	public static function baseDefaults( array &$data ) {
		
		// Application
		$data['{id}']			??= '';
		$data['{name}']			??= $data['{id}'];
		$data['{value}']		??= '';
		$data['{placeholder}']		??= '';
		$data['{required}']		??= '';
		
		$data['{extra}']		??= '';
		
		// Accessibility
		$data['{label}']		??= '';
		$data['{special}']		??= '';
		$data['{description}']		??= '';
		$data['{messages}']		??= '';
		
		// Append validation message holder, if needed
		$data['{input_message}']	??= (
			empty( $data['{messages}'] ) ? 
			'' : 
			\strtr( static::InputMessage, [ 
				'{messages}' => ( string ) $data['{messages}'] 
			] )
		);
		
		// Reset selected if not set
		$data['{selected}']  		??= '';
		
		// Overridden content
		$data['{input_field_before}']	??= '';
		$data['{input_field_after}']	??= '';
		
		$data['{input_before}']		??= '';
		$data['{input_after}']		??= '';
		
		$data['{label_before}']		??= '';
		$data['{label_after}']		??= '';
		$data['{special_before}']	??= '';
		$data['{special_after}']	??= '';
		$data['{desc_before}']		??= '';
		$data['{desc_after}']		??= '';
		
		$data['{special_extras}']	??= '';
		$data['{desc_extras}']		??= '';
		$data['{label_extras}']		??= '';
	}
	
	/**
	 *  Default styling from tachyons.css
	 */
	public static function baseStyling( array &$data ) {
		$data['{input_wrap_classes}']	??= 'pa4 black-80';
		$data['{label_classes}']	??= 
		match( $this ) { 
			// Radio labels are simpler
			InputType::Radio,
			InputType::Checkbox	=> 'f6 b lh-copy mb2',
			
			default			=> 'f6 b db mb2'
		};
		
		$data['{special_classes}']	??= 'normal black-80';
		$data['{desc_classes}']		??= 'f6 black-80';
		$data['{message_classes}']	??= 
			'input-message f6 measure-narrow bg-washed-red dark-red';
		
		$data['{input_classes}']	??= 
		match( $this ) {
			// Ticks
			InputType::Radio,
			InputType::Checkbox	=> 'b mr2',
			
			// Clickable
			InputType::Button,
			InputType::Submit	=> 
			'f6 button-reset pointer dim pa2 mb2 dib white bg-dark-blue',
			
			InputType::Reset	=> 
			'f6 button-reset pointer dim pa2 mb2 dib white bg-dark-red',
			
			// Multiline
			InputType::Textarea,
			InputType::Wysiwyg	=> 
			'db border-box black-80 w-100 ba b--black-50 pa2 mb2',
			
			default			=> 
			'input-reset db border-box black-80 w-100 ba b--black-50 pa2 mb2';
		};
	}
	
	public function addOptions( 
		\DOMElement	$e, 
		\DOMElement	$form, 
		array		$options 
	) {
		foreach ( $options as $o ) {
			// Option placeholders
			$data = \Notes\Util::placeholders( $o );
			
			// Base defaults
			$data = static::baseDefaults( $data );
			$data['{text}'] ??= '';
			
			$p = $form->ownerDocument->createElement( 'option', $data['{text}'] );
			foreach( $data as $k => $v ) {
				match( $k ) {
					'{selected}'	=> ( function() use ( $v, $p ) {
						if ( !empty( $v ) ) {
							$p->setAttribute( 'selected', 'selected' );
						}
					})(),
					
					'{id}'		=>
					$p->setAttribute( 'id', $data['{id}'] ),
					
					// Skip everything else
					default		=> null
				};
			}
			
			$e->appendChild( $p );
		}
	}
}

