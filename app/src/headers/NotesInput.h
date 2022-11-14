#ifndef NOTESINPUT_H
#define NOTESINPUT_H

#include <stdio.h>
#include <string>
#include <iterator>
#include <vector>
#include <unordered_map>
#include <algorithm>


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

#endif


