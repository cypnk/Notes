#!/bin/bash

# Binary destination and code source
BD=bin
SC=src

# Timestamp
DATE=`date +%Y-%m-%d-%H-%M-%S`

# File presets
IF=$BD/notes
OF=$BD/notes.sha1

# Data directory
DT=../data

# Document storage
DB=$DT/documents.db
SQ=$DT/documents.sql

# Backup of document storage
BK=data-backup-$DATE.sql

# Make bin directory
mkdir -p $BD

# Make and clean
make && make clean

# Create binary hash
sha1sum $IF > $OF

# Display
echo ''
echo 'Build complete'
echo ''
if [ -f $DB ]; then
	sqlite3 $DB .dump > $BK
	echo 'Document database backed up'
else
	sqlite3 $DB < $SQ
	echo 'Document database created'
fi
echo ''
cat $OF

# Permissions
chmod -R 0755 $DT
echo 'Permissions set'
echo ''

exit

