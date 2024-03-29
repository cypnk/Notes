
# Adapted from the excellent article: 
# https://spin.atomicobject.com/2016/08/26/makefile-c-projects/

# Executable out
TARGET		?= notes

# Sources directory
SRC_DIR		?= ./src

# Build objects directory (will be created if it doesn't exist)
BUILD_DIR	?= ./build

# Binaries location
BIN_DIR		?= ./bin

# Find sources
SRCS		:= $(shell find $(SRC_DIR) -name '*.cpp' -or -name '*.c' -or -name '*.s' | LC_ALL=C sort)

# Create object path
OBJS		:= $(SRCS:%=$(BUILD_DIR)/%.o)

# Dependencies
DEPS		:= $(OBJS:.o=.d)

# Include settings
INC_DIR		:= $(shell find $(SRC_DIR) -type d | LC_ALL=C sort)
INC_FLAGS	:= $(addprefix -I,$(INC_DIR))
LD_FLAGS	?= -Wl

# C flags (Mostly SQLite stuff)
CFLAGS		?= -DSQLITE_OMIT_DEPRECATED -DSQLITE_DQS=0 \
-DSQLITE_ENABLE_API_ARMOR -DSQLITE_OMIT_BLOB_LITERAL \
-DSQLITE_MAX_EXPR_DEPTH=0 -DSQLITE_TEMP_STORE=2 \
-DSQLITE_OMIT_EXPLAIN -DSQLITE_OMIT_LOAD_EXTENSION \
-DSQLITE_ENABLE_QPSG -DSQLITE_OMIT_DECLTYPE -DSQLITE_OMIT_SHARED_CACHE \
-DSQLITE_USE_ALLOCA -DSQLITE_OMIT_TCL_VARIABLE \
-DSQLITE_DEFAULT_MEMSTATUS=0 -DSQLITE_DEFAULT_WAL_SYNCHRONOUS=1 \
-DSQLITE_ENABLE_FTS3 -DSQLITE_ENABLE_FTS3_PARENTHESIS \
-DSQLITE_ENABLE_FTS3_TOKENIZER -DSQLITE_ENABLE_FTS4 \
-DSQLITE_LIKE_DOESNT_MATCH_BLOBS -DSQLITE_ENABLE_JSON1 \
-DSQLITE_OMIT_AUTHORIZATION -DSQLITE_ENABLE_PREUPDATE_HOOK \
-DSQLITE_ENABLE_SESSION

# C++ Flags
CXXFLAGS	?= $(INC_FLAGS) -MMD -MP -Wall -Wextra -Wconversion \
-Wunreachable-code -Wshadow -Werror -pedantic -std=c++17 -O2

# Detect OS
ifeq ($(OS),Windows_NT)
	# Make target.exe
	TARGET		:= $(TARGET).exe
	
	LD_FLAGS	+= -mwindows -static-libgcc -static-libstdc++
else
	LD_FLAGS	+= ,-Bstatic
endif


$(BIN_DIR)/$(TARGET): $(OBJS)
	$(CXX) $(OBJS) -o $@ $(LDFLAGS) `sdl2-config --cflags --libs` \
	-l SDL2 -l SDL2_ttf -ldl


# Assembly
$(BUILD_DIR)/%.s.o: $.s
	$(MKDIR_P) $(dir $@)
	$(AS) $(ASFLAGS) -c $< -o $@

# C source
$(BUILD_DIR)/%.c.o: %.c
	$(MKDIR_P) $(dir $@)
	$(CC) $(CPPFLAGS) $(CFLAGS) -c $< -o $@

# C++ source
$(BUILD_DIR)/%.cpp.o: %.cpp
	$(MKDIR_P) $(dir $@)
	$(CXX) $(CPPFLAGS) $(CXXFLAGS) -c $< -o $@

.PHONY:	clean

clean:
	$(RM) -r $(BUILD_DIR)

-include $(DEPS)
MKDIR_P		?= mkdir -p


