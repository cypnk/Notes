<?php declare( strict_types = 1 );

namespace Notes;

class ResourceFile extends Content {
	
	public string $src;
	
	public string $mime_type;
	
	public string $thumbnail;
	
	public int $file_size;
	
	public string $file_hash;
	
	public int $status;
	
	protected readonly array $_captions;
	
	/**
	 *  Valid image types to create a thumbnail
	 *  @var array
	 */
	private static $thumbnail_types	= [
		'image/jpeg',
		'image/png',
		'image/gif',
		'image/bmp',
		'image/webp'
	];
	
	public function __get( $name ) {
		switch ( $name ) {
			case 'captions':
				return $this->_captions ?? [];
		}
		
		return parent::__get( $name );
	}
	
	/**
	 *  Filter upload file name into a safe format
	 *  
	 *  @param string	$name		Original raw filename
	 *  @return string
	 */
	public static function filterUpName( ?string $name ) : string {
		if ( empty( $name ) ) {
			return '_';
		}
		
		$name	= \preg_replace('/[^\pL_\-\d\.\s]', ' ' );
		return \preg_replace( '/\s+/', '-', \trim( $name ) );
	}
	
	/**
	 *  Rename file to prevent overwriting existing ones by 
	 *  appending _i where 'i' is incremented by 1 until no 
	 *  more files with the same name are found
	 *   
	 *  @param string	$up		Unmodified filename
	 *  @return string
	 */
	public static function dupRename( string $up ) {
		$info	= \pathinfo( $up );
		$ext	= \Notes\Util::filterExt( $info['extension'] ?? '' );
		$name	= $info['filename'] ?? '';
		$dir	= $info['dirname'];
		$file	= $up;
		$i	= 0;
		
		while ( \file_exists( $file ) ) {
			$file = \PubCabin\Util::slashPath( $dir, true ) . 
				$name . '_' . $i++ . 
				\rtrim( '.' . $ext, '.' );
		}
		
		return $file;
	}
	
	/**
	 *  Given a compelete file path, prefix a term to the filename and 
	 *  return a unique file name path
	 *  
	 *  @param string	$path		Base file path
	 *  @param string	$prefix		Special file prefix added to path
	 */
	public static function prefixPath( string $path, string $prefix ) {
		$tn	= 
		\PubCabin\Util::slashPath( \dirname( $path ), true ) . 
			$prefix . \basename( $path );
		
		// Avoid duplicates
		return static::dupRename( $tn );
	}
	
	/**
	 *  Create image thumbnails from file path and given mime type
	 *  
	 *  @param string	$src	File source location
	 *  @param string	$mime	Image mime type
	 */
	public static function createThumbnail( 
		string	$src,
		string	$mime 
	) : string {
		
		// Get size and set proportions
		list( $width, $height ) = \getimagesize( $src );
		$t_width	= 100;
		$t_height	= ( $t_width / $width ) * $height;
		
		// New thumbnail
		$thumb		= \imagecreatetruecolor( $t_width, $t_height );
		
		// Create new image
		$source		= 
		match( $mime ) {
			'image/png'	=> ( function() use ( &$thumb, $src ) { 
				// Set transparent background
				\imagesavealpha( $thumb, true );
				return \imagecreatefrompng( $src );
			} )(),
			'image/gif'	=> \imagecreatefromgif( $src ),
			'image/bmp'	=> \imagecreatefrombmp( $src ),
			'image/webp'	=> \imagecreatefromwebp( $src ), 
			default		=> \imagecreatefromjpeg( $src )
		};
		
		// Resize to new resources
		\imagecopyresized( $thumb, $source, 0, 0, 0, 0, 
			$t_width, $t_height, $width, $height );
		
		// Thunbnail destination
		$dest	= static::prefixPath( $src, 'tn_' );
		
		// Create thumbnail at destination
		$tn	= 
		match( $mime ) {
			'image/png'	=> \imagepng( $thumb, $dest, 100 ),
			'image/gif'	=> \imagegif( $thumb, $dest, 100 ),
			'image/bmp'	=> \imagebmp( $thumb, $dest, 100 ),
			'image/webp'	=> \imagewebp( $thumb, $dest, 100 ),
			default		=> \imagejpeg( $thumb, $dest, 100 )
		};
		
		// Did anything go wrong?
		if ( false === $tn ) {
			return '';
		}
		
		// Cleanup
		\imagedestroy( $thumb );
		
		return $dest;
	}
	
	/**
	 *  File mime-type detection helper
	 *  
	 *  @param string	$path	Fixed file path
	 *  @return string
	 */
	public static function detectMime( string $path ) : string {
		$ext = \pathinfo( $path, \PATHINFO_EXTENSION ) ?? '';
			
		// Simpler text types
		return 
		match( \strtolower( $ext ) ) {
			'txt'	=> 'text/plain',
			'css'	=> 'text/css',
			'js'	=> 'text/javascript',
			'svg'	=> 'image/svg+xml',
			'vtt'	=> 'text/vtt',
			
			default	=> ( function() use ( $mime ) {
				// Detect other mime types
				$mime = \mime_content_type( $path );
		
				return ( false === $mime ) ? 
					'application/octet-stream' : $mime;
			} )()
		};
	}

	
	/**
	 *  Format uploaded file info for storage or database metadata
	 *  
	 *  @param string	$src	File original location
	 *  @return array
	 */
	public static function processFile( string $src, ) : array {
		$mime	= static::detectMime( $src );
		
		return [
			'src'		=> $src,
			'mime'		=> $mime,
			'file_name'	=> \basename( $src ),
			'file_size'	=> \filesize( $src ),
			'description'	=> '',
			
			// Process thumbnail if needed
			'thumbnail'	=>
				\in_array( $mime, static::$thumbnail_types ) ? 
				static::createThumbnail( $src, $mime ) : ''
		];
	}
	
	/** 
	 *  Return uploaded $_FILES array into a more sane format
	 * 
	 *  @return array
	 */
	public static function parseUploads() : array {
		$files = [];
		
		foreach ( $_FILES as $name => $file ) {
			if ( \is_array($file['name']) ) {
				foreach ( $file['name'] as $n => $f ) {
					$files[$name][$n] = [];
					
					foreach ( $file as $k => $v ) {
						$files[$name][$n][$k] = 
							$file[$k][$n];
					}
				}
				continue;
			}
			
        		$files[$name][] = $file;
		}
		return $files;
	}
	
	/**
	 *  Move uploaded files to the same directory as the post
	 *  
	 *  @param string	$path	Full upload destination directory
	 *  @param string	$root	Root destination prefix
	 */
	public static function saveUploads( 
		string	$path, 
		string	$root
	) : array {
		$files	= static::parseUploads();
		$store	= 
		\PubCabin\Util::slashPath( $root, true ) . 
		\PubCabin\Util::slashPath( $path, true );
		
		$saved	= [];
		
		
		// Intercept potential warnings as error
		\set_error_handler( function( 
			$eno, $emsg, $efile, $eline 
		) {
			logException( new \ErrorException( $emsg, 0, $eno, $efile, $eline ) );
		}, E_WARNING | \E_USER_WARNING );
		
		foreach ( $files as $name ) {
			foreach( $name as $file ) {
				// If errors were found, skip
				if ( $file['error'] != \UPLOAD_ERR_OK ) {
					\trigger_error( 'Error handling upload: ' . $name, \E_USER_WARNING );
					continue;
				}
				
				$tn	= $file['tmp_name'];
				$n	= 
				static::filterUpName( $file['name'] );
				
				// Check for duplicates and rename 
				$up	= static::dupRename( $store . $n );
				if ( \move_uploaded_file( $tn, $up ) ) {
					$saved[] = $up;
				}
			}
		}
		
		// Once uploaded and moved, format info
		$processed	= [];
		foreach( $saved as $k => $v ) {
			$processed[] = static::processFile( $v );	
		}
		
		\restore_error_handler();
		
		return $processed;
	}
	
	/**
	 *  Handle PUT method file upload
	 *  
	 *  @param string	$path	Uploading destination
	 *  @param string	$store	Root storage directory
	 *  @return array
	 */
	public static function saveStream( 
		string	$path, 
		string	$store
	) : array {
		$src	= '';
		$done	= false;
		
		// Intercept potential warnings as error
		\set_error_handler( function( 
			$eno, $emsg, $efile, $eline 
		) {
			logException( new \ErrorException( $emsg, 0, $eno, $efile, $eline ) );
		}, E_WARNING | \E_USER_WARNING );
		
		// Temp storage
		$tmp	= \tmpnam( $store, 'upload' );
		if ( false === $tmp ) {
			\trigger_error( 'Unable to create temp file in ' . $store, \E_USER_WARNING );
			return [];
		}
		
		$wr	= \fopen( $tmp, 'w' );
		if ( false === $wr ) {
			\unlink( $tmp );
			\trigger_error( 'Unable to open temp file ' . $tmp, \E_USER_WARNING );
			return [];
		}
		
		$stream	= \fopen( 'php://input', 'r' );
		if ( false === $stream ) {
			\fclose( $wr );
			\unlink( $tmp );
			\unset( $stream );
			
			\trigger_error( 'Cannot open upload stream php://input', \E_USER_WARNING );
			return [];
		}
		
		\stream_set_chunk_size( $stream, self::PUT_CHUNK );
		$ss = \stream_copy_to_stream( $stream, $wr );
		
		// Cleanup
		\fclose( $stream );
		\fclose( $wr );
		
		$fs = \filesize( $tmp );
		
		// Compare file size to total written bytes
		if ( 
			false === $ss || 
			false === $fs || 
			$ss != $fs 
		) {
			\unlink( $tmp );
			\trigger_error( 'Corrupted or empty data in ' . $tmp, \E_USER_WARNING );
			return [];
		}
		
		// Exract file path from destination
		$name	= static::filterUpName( \basename( $path ) );
		$src	= static::dupRename( $store . $name );
		
		if ( !\rename( $tmp, $src ) ) {
			\unlink( $tmp );
			\trigger_error( 'Cannot move temp file ' . $tmp, \E_USER_WARNING );
			return [];
		}
		
		\restore_error_handler();
		return [ static::processFile( $src ) ];
	}
	
	public function save() : bool {
		$rf = isset( $this->id ) ? true : false;
		
		if ( !$rf ) {
			if ( empty( $this->_content['file_size'] ) ) {
				$this->error( 'Empty file being saved' );
			}
			
			// Can't proceed without source
			if ( empty( $this->_content['src'] ) ) {
				$this->error( 'Attempted save without setting file source' );
				return false;
			}
		}
		
		$params	= [
			':content'	=> 
				static::formatSettings( $this->_content ),
			':status'	=> $this->status ?? 0
		];
		
		$db	= $this->getControllerParam( '\\\Notes\\Data' );
		if ( $rf ) {
			$params[':id']	= $this->id;
			return 
			$db->setUpdate(
				"UPDATE resources SET content = :content, status = :status
					WHERE id = :id;",
				$params,
				\DATA
			);
		}
		
		$id	= 
		$db->setInsert(
			"INESRT INTO resources ( content, status ) 
				VALUES ( :content, :status );",
			$params,
			\DATA
		);
		
		if ( empty( $id ) ) {
			return false;
		}
		$this->id = $id;
		return true;
	}
}

