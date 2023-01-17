#ifndef NOTES_H
#define NOTES_H

// On Visual Studio
//#define _CRT_SECURE_NO_WARNINGS

//#include <fstream>
//#include <sstream>
#include <SDL2/SDL.h>
#include "NotesCommands.h"
#include "NotesFormatting.h"
//#include "NotesData.h"
#include "NotesInput.h"

/**
 *  Main event loop delay
 */
#define LOOP_WAIT	30

// Color option
struct
RGB {
	Uint8 R	= 0;
	Uint8 B	= 0;
	Uint8 G	= 0;
	Uint8 A	= 1;
};

// Default colors
struct
COLORS {
	// Standard
	RGB black	{ 0, 0, 0, 1 };
	RGB white	{ 255, 255, 255, 1 };
	
	RGB graphite	{ 34, 34, 34, 1 };
	RGB coal	{ 122, 122, 113, 1 };
	
	RGB paper	{ 245, 244, 226, 1 };
	RGB cream	{ 252, 251, 245, 1 };
	RGB vanilla	{ 255, 255, 232, 1 };
	
	
	// Material UI https://materialuicolors.co (300)
	RGB grey	{ 224, 224, 224, 1 };
	RGB bgrey	{ 144, 164, 174, 1 };
	
	RGB cyan	{ 77, 208, 225, 1 };
	RGB blue	{ 100, 181, 246, 1 };
	RGB indigo	{ 121, 134, 203, 1 };
	
	RGB purple	{ 186, 104, 200, 1 };
	RGB dpurple	{ 149, 117, 205, 1 };
	
	RGB yellow	{ 255, 241, 118, 1 };
	RGB green	{ 129, 199, 132, 1 };
	RGB lgreen	{ 174, 213, 129, 1 };
	RGB teal	{ 77, 182, 172, 1 };
	RGB lime	{ 220, 231, 117, 1 };
	
	RGB pink	{ 240, 98, 146, 1 };
	RGB red		{ 229, 115, 115, 1 };
	RGB brown	{ 161, 136, 127, 1 };
	
	// Flat UI https://flatuicolors.com/palette/defo
	RGB turquoise	{ 26, 188, 156, 1 };
	RGB emarald	{ 46, 204, 113, 1 };
	RGB peter_river	{ 52, 152, 219, 1 };
	RGB amethyst	{ 155, 89, 182, 1 };
	RGB wet_asphalt	{ 52, 73, 94, 1 };
	RGB green_sea	{ 22, 160, 133, 1 };
	RGB nephritis	{ 39, 174, 96, 1 };
	RGB beliz_hole	{ 41, 128, 185, 1 };
	RGB wisteria	{ 142, 68, 173, 1 };
	RGB midnight_blue { 44, 62, 80, 1 };
	RGB sunflower	{ 241, 196, 15, 1 };
	RGB carrot	{ 230, 126, 34, 1 };
	RGB alizarin	{ 231, 76, 60, 1 };
	RGB clouds	{ 236, 240, 241, 1 };
	RGB concrete	{ 149, 165, 166, 1 };
	RGB orange	{ 243, 156, 18, 1 };
	RGB pumpkin	{ 211, 84, 0, 1 };
	RGB pomegranate { 192, 57, 43, 1 };
	RGB silver	{ 189, 195, 199, 1 };
	RGB asbestos	{ 127, 140, 141, 1 };
} COLORS;

#endif

