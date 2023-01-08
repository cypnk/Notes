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
	case Datalist;
	
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
			InputType::Hidden,
			InputType::Datalist	=> $node,
			
			// Wrap everything else
			default			=>
			$this->wrapInput( $node, $form, $data )
		}
	}
	
	protected function buildNode( \DOMElement $form, array $input, array $data ) {
		$node = 
		match( $this ) {
			// Datetime
			InputType::DateTime	=> 
			static::buildDatetime( $form, $data ),
			
			// Select
			InputType::Select	=> 
			static::buildSelect( $form, $data, $input ),
			
			// Multiline, formatted start with a textarea
			InputType::Textarea,
			InputType::Wysiwyg	=> 
			static::buildMultiline( $form, $data, $input ),
			
			// Selection list
			InputType::Datalist	=>
			static::buildDatalist( $form, $data, $input ),
			
			// Default input type
			default		=>  ( function() use ( $form, $data ) {
				$e = $form->ownerDocument->createElement( 'input' );
				$e->setAttribute( 'type', $data['{type}'] );
				$e->setAttribute( 'value', $data['{value}'] );
				
				return $e;
			} )()
		};
		
		$class = 
		match( $input['type'] ) {
			'hidden'	=> '',
			default		=> $data['{input_classes}']
		};
		$e->setAttribute( 'class', $class );
		
		$node->setAttribute( 'id', $data['{id}'] );
		$node->setAttribute( 'name', $data['{name}'] );
		
		// Append additional attributes
		static::addAttributes( $node, $data );
		
		// Apply any extra parameters
		\Notes\FormType::addExtras( $node, $input );
		
		return $node;
	}
	
	// TODO
	public function validate( array $input ) {
		
	}
	
	/**
	 *  Form field wrapper
	 */
	protected function wrapInput( 
		\DOMElement	$node, 
		\DOMElement	$form, 
		array		$data 
	) {
		$wrap = $form->ownerDocument->createElement( 'p' );
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
				$w = $form->ownerDocument->createElement( 'div', '' );
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
		static::addMessages( $wrap, $form, $data );
		
		// Post-input event content
		$wrap->appendChild(
			$form->ownerDocument->createTextNode( 
				'{input_field_after}' 
			)
		);
		
		// Add description if given
		static::addDescription( $wrap, $form, $data );
		
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
		
		$label = $form->ownerDocument->createElement( 'label', $data['{label}'] );
		$label->setAttribute( 'for', $data['{id}'] );
		$label->setAttribute( 'class', $data['{label_classes}'] );
		
		static::addSpecial( $label, $form, $data );
		
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
	public static function addSpecial( 
		\DOMElement	$label, 
		\DOMElement	$form, 
		array		$data 
	) {
		if ( empty( $data['{special}'] ) ) {
			return;
		}
		// Pre-special event content
		$label->appendChild(
			$form->ownerDocument->createTextNode( 
				'{special_before}' 
			)
		);
		
		$special = $form->ownerDocument->createElement( 'span', $data['{special}'] );
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
	 *  Add common input elements
	 */
	public static addAttributes( \DOMElement $node, array $data ) {
		
		foreach ( $data as $k => $v ) {
			$success = 
			match( $k ) {
				// Has a placeholder
				'{placeholder}'	=>
				$node->setAttribute( 
					'placeholder', ( string ) $v 
				),
				
				// Required?
				'{required}'	=>
				$node->setAttribute( 'required', 'required' ),
				
				// Has a description?
				'{desc}'	=>
				$node->setAttribute( 
					'aria-described-by', 
					( string ) $data['{id}'] . '-desc' 
				),
				
				// HTML5 validation pattern?
				'{pattern}'	=>
				$node->setAttribute( 
					'pattern', ( string ) $data['{pattern}'] 
				),
				
				// Allow multiple?
				'{multiple}'	=>
				$node->setAttribute( 'multiple', 'multiple' ),
				
				// Autocomplete list?
				'{list}'	=>
				$node->setAttribute( 
					'list', 
					\Notes\Util::bland( 
						( string ) $data['list'] 
					)
				),
				
				default		=> true
			};
			
			if ( !$success ) {
				// Error setting attributes, skip rest
				break;
			}
		}
	}
	
	/**
	 *  Input content instructions
	 */
	protected static function addDescription( 
		\DOMElement	$wrap, 
		\DOMElement	$form, 
		array		$data 
	) {
		if ( empty( $data['{desc}'] ) ) {
			return;
		}
		// Pre-descriptipon event content
		$wrap->appendChild(
			$form->ownerDocument->createTextNode( 
				'{desc_before}' 
			)
		);
		
		$desc = $form->ownerDocument->createElement( 'small', $data['{desc}'] );
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
	public static function addMessages(
		\DOMElement	$wrap, 
		\DOMElement	$form, 
		array		$data,
		string		$wtype	= 'span'
	) {
		if ( empty( $data['{messages}'] ) ) {
			return;
		}
		$msg = 
		$form->ownerDocument->createElement( 
			$wtype, $data['{messages}'] 
		);
		
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
		$data['{extras}']		??= [];
		
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
	
	public static function addOptions( 
		\DOMElement	$e, 
		\DOMElement	$form, 
		array		$options 
	) {
		foreach ( $options as $o ) {
			// Option placeholders
			$data = \Notes\Util::placeholders( $o );
			
			// Base defaults
			$data = static::baseDefaults( $data );
			
			$p = 
			$form->ownerDocument->createElement( 
				'option', $data['{text}'] ?? ''
			);
			foreach( $data as $k => $v ) {
				match( $k ) {
					'{selected}'	=> 
					( function() use ( $v, $p ) {
						if ( !empty( $v ) ) {
							$p->setAttribute( 
								'selected', 
								'selected' 
							);
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
	
	/**
	 *  Dropdown select
	 */
	public static function buildSelect( 
		\DOMElement	$form, 
		array		$data, 
		array		$input 
	) : \DOMElement {
		$e = $form->ownerDocument->createElement( 'select' );
		
		// Set unselected (empty) option
		if ( !empty( $data['{unselect_option}'] ) ) {
			$u = $form->ownerDocument->createElement( 'option', '--' );
			$e->appendChild( $u );
		} 
		static::addOptions( $e, $form, $input['options'] ?? [] );
		return $e;
	}
	
	/**
	 *  Specific date selection
	 */
	public static function buildDatetime( 
		\DOMElement	$form, 
		array		$data 
	) : \DOMElement {
		$e = $form->ownerDocument->createElement( 'input' );
		
		// Override type
		$e->setAttribute( 'type', 'datetime-local' );
		$e->setAttribute( 'value', $data['{value}'] );
		
		return $e;
	}
	
	/**
	 *  Textareas and wysiwyg
	 */
	public static function buildMultiline( 
		\DOMElement	$form, 
		array		$data, 
		array		$input 
	) : \DOMElement { 
		$e = $form->ownerDocument->createElement( 
			'textarea', $input['{value}']
		);
		
		$e->setAttribute( 'rows', $input['rows'] ?? 5 );
		$e->setAttribute( 'cols', $input['cols'] ?? 50 );
		return $e;
	}
	
	/**
	 *  Background data retrieval for autocomplete types
	 */
	public static function builDatalist( 
		\DOMElement	$form, 
		array		$data, 
		array		$input 
	) : \DOMElement {
		// Background autocomplete URL
		$input['url']	??= '';
		$input['url']	= \Notes\Util::cleanUrl( $input['url'] );
		
		$e = $form->ownerDocument->createElement( 'datalist' );
		
		// Default options, if any
		static::addOptions( $e, $form, $input['options'] ?? [] );
		
		// Set auto-complete URL, if given
		if ( !empty( $input['ur'] ) ) {
			$e->setAttribute( 'data-url', $input['url'] );
		}
		
		// Refresh interval or event E.G. 10 for seconds or 'keyup'
		if ( !empty( $input['refresh'] ) ) {
			$e->setAttribute( 
				'data-refresh', 
				\Notes\Util::bland( $input['refresh'] )
			);
		}
		
		return $e;
	}
}

