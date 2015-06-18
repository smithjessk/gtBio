CC=clang++
CFLAGS=-std=c++11 -g
DEPS=src/CelStructure.h

default:
	$(CC) $(CFLAGS) -o bin/readFile src/readFile.cpp -I.