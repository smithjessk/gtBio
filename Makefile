CC=clang++
CFLAGS=-std=c++11 -g
DEPS=CelStructure.h

default:
	$(CC) $(CFLAGS) -o readFile readFile.cpp -I.