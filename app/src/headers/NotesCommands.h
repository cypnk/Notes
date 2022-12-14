#ifndef NOTESCOMMANDS_H
#define NOTESCOMMANDS_H

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


#endif


