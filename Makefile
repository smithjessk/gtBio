CC=clang++
CFLAGS=-std=c++11 -g -O2 -larmadillo
DEPS=src/CelStructure.h

GTEST_DIR=/home/jess/Downloads/gtest-1.7.0
USER_DIR = test

# Do not modify unless you know what you're doing
GTEST_HEADERS = $(GTEST_DIR)/include/gtest/*.h \
                $(GTEST_DIR)/include/gtest/internal/*.h
CPPFLAGS += -lgtest -std=c++11
CXXFLAGS += -g -Wall -Wextra -pthread

# Add new tests as they are created
TESTS = fileReaderUnitTest

all : core $(TESTS)

tests : $(TESTS)

core:
	$(CC) $(CFLAGS) -o bin/readFile src/readFile.cpp -I.

clean:
	rm -f $(TESTS) gtest.a gtest_main.a *.o bin/readFile

GTEST_SRCS_ = $(GTEST_DIR)/src/*.cc $(GTEST_DIR)/src/*.h $(GTEST_HEADERS)

# Build Google testing framework
gtest-all.o : $(GTEST_SRCS_)
	$(CXX) $(CPPFLAGS) -I$(GTEST_DIR) $(CXXFLAGS) -c \
		$(GTEST_DIR)/src/gtest-all.cc

gtest_main.o : $(GTEST_SRCS_)
	$(CXX) $(CPPFLAGS) -I$(GTEST_DIR) $(CXXFLAGS) -c \
		$(GTEST_DIR)/src/gtest_main.cc

gtest.a : gtest-all.o
	$(AR) $(ARFLAGS) $@ $^

gtest_main.a : gtest-all.o gtest_main.o
	$(AR) $(ARFLAGS) $@ $^

# Build sample test

fileReaderUnitTest.o : $(USER_DIR)/fileReaderUnitTest.cpp \
					 $(GTEST_HEADERS)
	$(CXX) $(CPPFLAGS) $(CXXFLAGS) -c $(USER_DIR)/fileReaderUnitTest.cpp

fileReaderUnitTest : fileReaderUnitTest.o gtest_main.a
	$(CXX) $(CPPFLAGS) $(CXXFLAGS) -lpthread $^ -o $@