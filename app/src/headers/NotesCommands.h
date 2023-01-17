#ifndef NOTESCOMMANDS_H
#define NOTESCOMMANDS_H

#include <vector>
#include <SDL2/SDL.h>

/**
 *  Command key format
 */
struct
NOTES_COMMAND {
	SDL_Keycode	code	= SDLK_UNKNOWN;
	int		ctrl	= 0;
	int		shift	= 0;
	int		alt	= 0;  // Not used for now
	unsigned char	action	= 0x0000;
};

/**
 *  Keyboard commands
 */

/**
 *  Move commands
 */

// Basic movement
#define	M_UP		0x0000
#define	M_DOWN		0x0001
#define	M_LEFT		0x0002
#define M_RIGHT		0x0003

// Line movement
#define M_LNSTART	0x0004
#define M_LNEND		0x0005

// Scrolling
#define M_SCRLUP	0x0006
#define M_SCRLDN	0x0007

// Pagination
#define M_PGUP		0x0008
#define M_PGDN		0x0009

// Document start/end
#define M_DSTART	0x0010
#define M_DEND		0x0011

// Text selections
#define S_LEFT		0x0012
#define S_RIGHT		0x0013

// Find text
#define T_QUERY		0x0014

/**
 *  Editing commands
 */

// Delete left/right of cursor
#define	E_DELL		0x0015
#define	E_DELR		0x0016

// Delete current word/line
#define	E_DELWD		0x0017
#define	E_DELLN		0x0018

// Insert page break
#define E_BREAK		0x0019

// Copy to clipboard
#define C_COPY		0x0020
#define C_PASTE		0x0021

// Insert memo
#define P_MEMO		0x0022

// Create subroutine
#define P_PROG		0x0023

// History undo/redo
#define H_UNDO		0x0024
#define H_REDO		0x0025

// Document handling
#define T_NEW		0x0026
#define T_OPEN		0x0027

// Indent
#define T_INDENT	0x0028
#define T_ODENT		0x0029

// Superscript/subscript
#define T_SUP		0x0030
#define T_SUB		0x0031

// Basic formatting
#define T_BOLD		0x0032
#define T_ITALIC	0x0033
#define T_UNDER		0x0034

// Return cursor
#define M_CUR		0x0035
#define M_LCUR		0x0036

// Open clipboard
#define C_CLIP		0x0037

// Insert break
#define T_BREAK		0x0038

// Delete to start/end of line
#define	E_DELSL		0x0039
#define	E_DELEL		0x0040


/**
 *  Keyboard command map
 */
NOTES_COMMAND QWERTY_WD_MAP[] = {
	{ SDLK_UP,		0, 0, 0, M_UP },
	{ SDLK_e,		1, 0, 0, M_UP },
	
	{ SDLK_DOWN,		0, 0, 0, M_DOWN },
	{ SDLK_x,		1, 0, 0, M_DOWN },
	
	{ SDLK_LEFT,		0, 0, 0, M_LEFT },
	{ SDLK_s,		1, 0, 0, M_LEFT },
	
	{ SDLK_RIGHT,		0, 0, 0, M_RIGHT },
	{ SDLK_d,		1, 0, 0, M_RIGHT },
	
	{ SDLK_HOME,		0, 0, 0, M_LNSTART },
	{ SDLK_k,		1, 0, 0, M_LNSTART },
	
	{ SDLK_END,		0, 0, 0, M_LNEND },
	{ SDLK_l,		1, 0, 0, M_LNEND },
	
	// Line scroll
	{ SDLK_w,		1, 0, 0, M_SCRLUP },
	{ SDLK_z,		1, 0, 0, M_SCRLDN },
	
	// Pagination
	{ SDLK_PAGEUP,		0, 0, 0, M_PGUP },
	{ SDLK_r,		1, 0, 0, M_PGUP },
	
	{ SDLK_PAGEDOWN,	0, 0, 0, M_PGDN },
	{ SDLK_c,		1, 0, 0, M_PGDN },
	
	{ SDLK_HOME,		1, 0, 0, M_DSTART },
	{ SDLK_COMMA,		1, 0, 0, M_DSTART },
	
	{ SDLK_END,		1, 0, 0, M_DEND },
	{ SDLK_PERIOD,		1, 0, 0, M_DEND },
	
	// Move cursor to last position
	{ SDLK_j,		1, 0, 0, M_CUR },
	// Move cursor to last position
	{ SDLK_j,		1, 1, 0, M_LCUR },
	
	// Selections
	{ SDLK_LEFT,		0, 1, 0, S_LEFT },
	{ SDLK_a,		1, 0, 0, S_LEFT },
	
	{ SDLK_RIGHT,		0, 1, 0, S_RIGHT },
	{ SDLK_f,		1, 0, 0, S_RIGHT },
	
	// Find
	{ SDLK_q,		1, 0, 0, T_QUERY },
	
	// Editing
	{ SDLK_DELETE,		0, 0, 0, E_DELR },
	{ SDLK_g,		1, 0, 0, E_DELL },
	{ SDLK_h,		1, 0, 0, E_DELR },
	
	// Delete word/line
	{ SDLK_t,		1, 1, 0, E_DELWD },
	{ SDLK_y,		1, 1, 0, E_DELLN },
	
	// Clipboard
	{ SDLK_v,		1, 0, 0, C_CLIP },
	
	// Insert line break
	{ SDLK_b,		1, 0, 0, T_BREAK },
	
	// Copy paste
	{ SDLK_LEFTBRACKET, 	1, 0, 0, C_COPY },
	{ SDLK_RIGHTBRACKET,	1, 0, 0, C_PASTE },
	
	// Memo / Citation
	{ SDLK_m,		1, 0, 0, P_MEMO },
	
	// History
	{ SDLK_z,		1, 0, 0, H_UNDO },
	{ SDLK_y,		1, 1, 0, H_REDO },
	
	// Document handling
	{ SDLK_n,		1, 0, 0, T_NEW },
	{ SDLK_o,		1, 0, 0, T_OPEN },
	
	// Indentation
	{ SDLK_TAB,		0, 0, 0, T_INDENT },
	{ SDLK_TAB,		1, 0, 0, T_ODENT },
	
	// Formatting
	{ SDLK_t,		1, 1, 0, T_SUP },
	{ SDLK_v,		1, 1, 0, T_SUB },
	{ SDLK_b,		1, 1, 0, T_BOLD },
	{ SDLK_i,		1, 1, 0, T_ITALIC },
	{ SDLK_u,		1, 1, 0, T_UNDER },
	
	// Text deletion
	{ SDLK_BACKSPACE,	0, 0, 0, E_DELL },
	
	// Delete to start/end of line
	{ SDLK_BACKSPACE,	1, 0, 0, E_DELSL },
	{ SDLK_BACKSPACE,	1, 1, 0, E_DELEL },
	
	// Insert line break
	{ SDLK_RETURN,		0, 0, 0, T_BREAK },
	
	// Insert page break
	{ SDLK_RETURN,		1, 1, 0, E_BREAK },
	{ SDLK_b,		1, 1, 0, E_BREAK }
};



#endif


