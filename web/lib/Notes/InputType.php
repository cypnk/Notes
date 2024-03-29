<?php declare( strict_types = 1 );

namespace Notes;

enum InputType {
	
	
	// Base text types
	case Text;
	case Textarea;
	case Password;
	case DateTime;
	case Url;
	
	// Long formatted text
	case Wysiwyg;
	
	// Query types
	case Search;
	case Email;
	
	// Multiple values
	case Select;
	case Checkbox;
	case Radio;
	case Calendar;
	
	// Form processing
	case Hidden;
	case Datalist;
	
	// Buttons
	case Submit;
	case Clear; // Reset
	case Button;
	
	// TODO
	case Upload;
	case Number;
	case NumRange;
	
	// Any other type
	case Other;
	
	
	protected static string $input_message	= '{messages}';
	
	public function setMessage( $msg ) {
		static::$input_message = $msg;
	}
	
	/**
	 *  Render the current input type in the above templates
	 */
	public function buildInput( 
		\Notes\Controller	$ctrl,
		\DOMElement		$form, 
		array			&$input 
	) {
		// Default to text type if not given
		$input['type']	??= 'text';
		$input['type']	= \strtolower( $input['type'] );
		
		$input['value']	??= '';
		
		$parser		= $ctrl->getParam( '\\Notes\\Parser' );
		$data		= $parser->placeholders( $input );
		
		// Default settings
		static::baseDefaults( $data );
		
		// Default styling
		static::baseStyling( $data );
		
		// Input pre-build event
		$ctrl->run( 'input_init', $input );
		$out	= $ctrl->output( 'input_init' );
		$input	= [ ...$input, ...$out ];
		
		$node	= 
		$this->buildNode( $parser, $form, $input, $data );
		$node->setAttribute( 'id', $data['{id}'] );
		$node->setAttribute( 'name', $data['{name}'] );
		
		return 
		match( $this ) {
			// Send bare for inline types
			InputType::Submit,
			InputType::Button,
			InputType::Hidden,
			InputType::Calendar,
			InputType::Datalist	=> $node,
			
			// Wrap everything else
			default			=>
			$this->wrapInput( $ctrl, $node, $form, $data )
		}
	}
	
	protected function buildNode( 
		\Notes\Parser		$parser,
		\DOMElement		$form, 
		array			$input, 
		array			&$data 
	) {
		$node	= 
		match( $this ) {
			// Datetime
			InputType::DateTime	=> 
			static::buildDatetime( $form, $data ),
			
			// Select
			InputType::Select	=> 
			static::buildSelect( $parser, $form, $data, $input ),
			
			// Multiline, formatted start with a textarea
			InputType::Textarea,
			InputType::Wysiwyg	=> 
			static::buildMultiline( $form, $data, $input ),
			
			// Dates
			InputType::Calendar	=>
			static::buildCalendar( $parser, $form, $data, $input ),
			
			// Selection list
			InputType::Datalist	=>
			static::buildDatalist( $parser, $form, $data, $input ),
			
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
	 *  
	 *  @param \Notes\Controller	$ctrl	Web event controller
	 *  @param \DOMElement		$node	Input element node
	 *  @param \DOMElement		$form	Parent document form
	 *  @param array		$data	Content as placeholder values
	 *  @param string		$wtype	Element wrapper type
	 */
	protected function wrapInput( 
		\Notes\Controller	$ctrl,
		\DOMElement		$node, 
		\DOMElement		$form, 
		array			&$data,
		string			$wtype	=	'p'
	) : void {
		$wrap = 
		$form->ownerDocument->createElement( \strtolower( $wtype ) );
		$wrap->setAttribute( 'class', $data['{input_wrap_classes}'] );
		
		$this->addLabel( $wrap, $node, $form, $data );
		
		// Pre-input event content
		$wrap->appendChild(
			$form->ownerDocument->createTextNode( 
				'{input_field_before}' 
			)
		);
		
		$ctrl->run( 'input_field_before', $data );
		$out	= $ctrl->output( 'input_field_before' );
		$data	= [ ...$data, ...$out ];
		
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
		
		$ctrl->run( 'input_field_after', $data );
		$out	= $ctrl->output( 'input_field_after' );
		$data	= [ ...$data, ...$out ];
		
		// Add description if given
		static::addDescription( $ctrl, $wrap, $form, $data );
		
		return $wrap;
	}
	
	/**
	 *  Brief input description
	 */
	protected function addLabel( 
		\Notes\Controller	$ctrl,
		\DOMElement		$wrap, 
		\DOMElement		$node, 
		\DOMElement		$form, 
		array			&$data 
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
		
		$ctrl->run( 'label_before', $data );
		$out	= $ctrl->output( 'label_before' );
		$data	= [ ...$data, ...$out ]; 
		
		$label	= 
		$form->ownerDocument->createElement( 'label', $data['{label}'] );
		$label->setAttribute( 'id', $data['{id}'] . '-label' );
		$label->setAttribute( 'for', $data['{id}'] );
		$label->setAttribute( 'class', $data['{label_classes}'] );
		
		static::addSpecial( $ctrl, $label, $form, $data );
		
		match( $this ) {
			// Options handled differently
			InputType::Radio,
			InputType::Checkbox	=> 
			( function() use ( $label, $wrap, $node ) {
				$label->appendChild( $node );
				$wrap->appendChild( $label );
			} )(),
			
			// Add input label
			default			=> 
				$wrap->appendChild( $label )
		};
		
		// Post-label event content
		$wrap->appendChild(
			$form->ownerDocument->createTextNode( 
				'{label_after}' 
			)
		);
		
		$ctrl->run( 'label_after', $data );
		$out	= $ctrl->output( 'label_after' );
		$data	= [ ...$data, ...$out ];
	}
	
	/**
	 *  Special validation parameters E.G. "required" or "optional"
	 */
	public static function addSpecial( 
		\Notes\Controller	$ctrl,
		\DOMElement		$label, 
		\DOMElement		$form, 
		array			&$data 
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
		
		$ctrl->run( 'special_before', $data );
		$out		= $ctrl->output( 'special_before' );
		$data		= [ ...$data, ...$out ];
		
		$special	= 
		$form->ownerDocument->createElement( 'span', $data['{special}'] );
		
		$special->setAttribute( 'class', $data['{special_classes}'] );
		
		$label->appendChild( $special );
		
		// Post-special event content
		$label->appendChild(
			$form->ownerDocument->createTextNode( 
				'{special_after}' 
			)
		);
		
		$ctrl->run( 'special_after', $data );
		$out	= $ctrl->output( 'special_after' );
		$data	= [ ...$data, ...$out ];
	}
	
	/**
	 *  Add common input elements
	 */
	public static function addAttributes( \DOMElement $node, array $data ) {
		
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
				
				// Has a label?
				'{label}'	=>
				$node->setAttribute( 
					'aria-labelledby', 
					( string ) $data['{id}'] . '-label' 
				),
				
				// Has a description?
				'{desc}'	=>
				$node->setAttribute( 
					'aria-described-by', 
					( string ) $data['{id}'] . '-desc' 
				),
				
				// Has validation messages?
				'{messages}'	=> 
				$node->setAttribute( 'aria-controls', 
					( string ) $data['{id}'] . '-messages' 
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
						( string ) $data['{list}'] 
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
		\Notes\Controller	$ctrl,
		\DOMElement		$wrap, 
		\DOMElement		$form, 
		array			&$data 
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
		
		$ctrl->run( 'desc_before', $data );
		$out	= $ctrl->output( 'desc_before' );
		$data	= [ ...$data, ...$out ];
		
		$desc	= 
		$form->ownerDocument->createElement( 'small', $data['{desc}'] );
		$label->setAttribute( 'id', $data['{id}'] . '-desc' );
		$label->setAttribute( 'class', $data['{desc_classes}'] );
		$wrap->appendChild( $desc );
		
		// Post-descriptipon event content
		$wrap->appendChild(
			$form->ownerDocument->createTextNode( 
				'{desc_after}' 
			)
		);
		
		$ctrl->run( 'desc_after', $data );
		$out	= $ctrl->output( 'desc_after' );
		$data	= [ ...$data, ...$out ];
	}
	
	/**
	 *  Error/validity pseudo element message holder
	 *   
	 *  @param \DOMElement	$wrap	Container element
	 *  @param \DOMElement	$form	Parent document form
	 *  @param array	$data	Content as placeholder values
	 *  @param string	$wtype	Message output wrapper type
	 *  @param bool		$skipv	Enable empty message checking if true
	 *  @param string	$css	Custom CSS classes
	 *  
	 *  @example
	 *  input:valid ~ .input-message { display:none; }
	 *  input:required:invalid ~ .input-message::after { content: attr(data-required); }
	 *  input:invalid:not(:placeholder-shown) ~ .input-message::after { content: attr(data-validation); }
	 */
	public static function addMessages(
		\DOMElement	$wrap, 
		\DOMElement	$form, 
		array		$data,
		string		$wtype	= 'output',
		bool		$skipv	= false,
		string		$css	= ''
	) {
		if ( empty( $data['{messages}'] ) && !$skipv ) {
			return;
		}
		$msg = 
		$form->ownerDocument->createElement( 
			$wtype, $data['{messages}'] 
		);
		
		$msg->setAttribute( 'id', $data['{id}'] . '-messages' );
		$msg->setAttribute( 'for', $data['{id}'] );
		$msg->setAttribute( 'aria-live', 'polite' );
		$msg->setAttribute( 'role', 'region' );
		$msg->setAttribute( 'class', 
			empty( $css ) ? $data['{message_classes}'] : $css
		);
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
			\strtr( static::$input_message, [ 
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
			
			InputType::Clear	=> 
			'f6 button-reset pointer dim pa2 mb2 dib white bg-dark-red',
			
			// Multiline
			InputType::Textarea,
			InputType::Wysiwyg	=> 
			'db border-box black-80 w-100 ba b--black-50 pa2 mb2',
			
			default			=> 
			'input-reset db border-box black-80 w-100 ba b--black-50 pa2 mb2';
		};
		
		// Calendar
		$data['{calendar_classes}']	??= ''
		$data['{calendar_h_classes}']	??= '';
		$data['{calendar_dow_classes}']	??= '';
		$data['{calendar_gr_classes}']	??= '';
		$data['{calendar_day_classes}']	??= '';
		$data['{calendar_dt_classes}']	??= '';
		$data['{calendar_txt_classes}']	??= '';
		$data['{calendar_msg_classes}']	??= '';
	}
	
	public static function addOptions( 
		\Notes\Parser	$parser,
		\DOMElement	$e, 
		\DOMElement	$form, 
		array		$options 
	) {
		foreach ( $options as $o ) {
			// Option placeholders
			$data	= $parser->placeholders( $o );
			
			// Base defaults
			$data	= static::baseDefaults( $data );
			
			$p	= 
			$form->ownerDocument->createElement( 
				'option', $data['{text}'] ?? ''
			);
			
			// Set value or option text itself
			$p->setAttribute( 
				'value', 
				$o['value'] ?? ( \is_array( $o ) ? '' : $o )
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
		\Notes\Parser	$parser,
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
		static::addOptions( $parser, $e, $form, $input['options'] ?? [] );
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
	 *  Calendar days
	 */
	public static function buildCalendar( 
		\Notes\Parser	$parser,
		\DOMElement	$form, 
		array		$data, 
		array		$input 
	) : \DOMElement {
		$id = $input['id'] ?? '';
		
		$e = $form->ownerDocument->createElement( 'section' );
		$e->setAttribute( 'class', $data['{calendar_classes}'] );
		
		// Main month header
		$h = $form->ownerDocument->createElement( 'header' );
		$h->setAttribute( 
			'class', 
			$data['{calendar_h_classes}'] 
		);
		
		// Formatted month
		$t = 
		$form->ownerDocument
			->createElement( 
				'time', 
				$input['month_nice'] ?? '' 
			);
		
		$t->setAttribute( 'datetime', $input['month'] ?? '' );
		$h->appendChild( $t );
		
		// Days of the week
		$w = $form->ownerDocument->createElement( 'header' );
		$w->setAttribute( 'class', $data['{calendar_dow_classes}'] );
		
		// Monday, Tuesday etc... from language placeholders
		foreach ( \range( 1, 7, 1 ) as $wd ) {
			$ds = 
			$form->ownerDocument->createElement( 
				'div', $input['dow_' . $wd] ?? ''
			);
			$w->appendChild( $ds );
		}
		
		$e->appendChild( $h );
		$e->appendChild( $w );
		
		// Dates of the month
		$g = $form->ownerDocument->createElement( 'div' );
		$g->setAttribute( 'class', $data['{calendar_gr_classes}'] );
		$s = $input['start_day'] ?? '1';
		
		// Prepend dummy days until starting day of week
		for ( $i = $s; $i > -1; $i-- ) {
			$n = $form->ownderDocument->createElement( 'label' );
			$n->setAttribute( 'class', $data['{calendar_dayinv_classes}'] ?? '' );
			$n->setAttribute( 'data-day', $i );
			$g->appendChild( $n );
		}
		
		$j = $s;
		$i = 0;
		
		// Build inputs
		foreach ( $input['days'] as $dt ) {
			$day		= $parser->placeholders( $dt );
			$day['{id}']	= $id . $day['{day}'];
			
			// Date label
			$d	= $form->ownerDocument->createElement( 'label' );
			$n->setAttribute( 'id', $day['{id}'] . '-label' );
			$n->setAttribute( 'for', $day['{id}'] );
			$d->setAttribute( 'data-day', ( string ) $j );
			$d->setAttribute( 'class', $data['{calendar_day_classes}'] );
			
			$t	= $form->ownerDocument->createElement( 'input' );
			$t->setAttribute( 'id', $day['{id}'] );
			$t->setAttribute( 'name', $id . '[][' . $day['{day}'] . ']' );
			$t->setAttribute( 'type', 'text' );
			$t->setAttribute( 'data-day', ( string ) $i );
			$t->setAttribute( 'value', $day['{value}'] ?? '' );
			$t->setAttribute( 'class', 
				$data['{calendar_txt_classes}'] ?? '' 
			);
			
			// Date stamp
			$s	= 
			$form->ownerDocument
				->createElement( 
					'time', $day['{day}'] ?? '' 
				);
			
			$s->setAttribute( 'datetime', $day['{day_utc}' ?? '' );
			$s->setAttribute( 'class', $data['{calendar_dt_classes}'] );
			
			$d->appendChild( $t );
			$d->appendChild( $s );
			
			// Messages
			static::addMessages( 
				$d, $form, $day, 
				'output', true, 
				$data['{calendar_msg_classes}'] 
			);
			
			$g->appendChild( $d );
			
			$i++;
			$j++;
		}
		
		$e->appendChild( $g );
		return $e;
	}
	
	/**
	 *  Background data retrieval for autocomplete types
	 */
	public static function buildDatalist( 
		\Notes\Parser	$parser,
		\DOMElement	$form, 
		array		$data, 
		array		$input 
	) : \DOMElement {
		// Background autocomplete URL
		$input['url']	??= '';
		$input['url']	= \Notes\Util::cleanUrl( $input['url'] );
		
		$e = $form->ownerDocument->createElement( 'datalist' );
		
		// Default options, if any
		static::addOptions( $parser, $e, $form, $input['options'] ?? [] );
		
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

