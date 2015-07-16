#/bin/sh

make clean
make core
make tests
./bin/fileReaderUnitTest
make clean