#ifndef NOTESINPUT_H
#define NOTESINPUT_H

#include <stdio.h>
#include <string>
#include <iterator>
#include <vector>
#include <unordered_map>
#include <algorithm>

// Space markers
#define SPC_MKR		" \t\r\v\f\n"

// Command line parameter ( -c value format )
struct
CMD_PARAM {
	std::string opt;
	std::string value;
};

// Command line parameter search
inline std::string
CMD_FIND(
	std::string		opt,	// Option to search
	std::string		def,	// Fallback value
	std::vector<CMD_PARAM>&	params	// Search params
) {
	auto it = std::find_if( 
		params.begin(), params.end(),
		[ = ]( const CMD_PARAM& find ) -> bool {
			return 
			find.opt == opt;
		}
	);
	
	if ( it != params.end() ) {
		return ( *it ).value;
	}
	return def;
}


/**
 *  Helpers
 */
#define ARRAY_SIZE( a ) \
	( sizeof( a ) / sizeof( *( a ) ) )


static inline void
rtrim( std::string& str ) {
	if ( str.size() == 0 ) {
		return;
	}
	
	// From the end
	std::size_t last = str.find_last_of( SPC_MKR );
	
	// Space found?
	if ( last != std::string::npos ) {
		str.erase( last + 1 );
	}
}

static inline void
ltrim( std::string& str ) {
	std::size_t sz	= str.size();
	
	if ( sz == 0 ) {
		return;
	}
	// From the front
	std::size_t first = str.find_first_of( SPC_MKR );
	
	// Space found?
	if ( first != std::string::npos ) {
		str	= str.substr( first, ( sz - first + 1 ) );
	}
}

static inline void
trim( std::string& str ) {
	rtrim( str );
	ltrim( str );
}


#endif


