#ifndef CEL_FILE_READER
#define CEL_FILE_READER

#include <memory>
#include <unistd.h>
#include <unistd.h>
#include <sys/stat.h>
#include <stdio.h>
#include <sys/types.h>
#include <fcntl.h>
#include <sys/mman.h>

#include "CELBase.h"
#include "Errors.h"
#include "CELStructureCC.h"
#include "CELStructureV4.h"

class CELFileReader {
private:
    char *fileName;
    int fd;
    off_t size;
    char* map;

    int genFD(char *fileName) {
        int fd = open(fileName, O_RDONLY);
        if (fd == -1) {
            perror("Error opening file for reading");
            exit(EXIT_FAILURE);
        }
        return fd;
    }

    char* genMap(int fd, off_t size) {
        char *map = (char *) mmap(NULL, size, PROT_READ, MAP_SHARED, fd, 0);
        if (map == MAP_FAILED) {
            close(fd);
            perror("Error mmapping the file");
            exit(EXIT_FAILURE);
        }
        return map;
    }

public:
    CELFileReader(char *fileName) {
        fileName = fileName;
        fd = genFD(fileName);
        size = lseek(fd, 0, SEEK_HOLE);
        map = genMap(fd, size);
    }

    /** Read the file referenced by this CELFileReader and return the result */
    CELBase::pointer readFile() {
        uint8_t magic = unsigned(*((uint8_t*) map));
        if (magic == 59) {
            return CELCommandConsole::pointer(new CELCommandConsole(map));
        } else if (magic == 64) {
            return CEL4::pointer(new CEL4(map));
        } else {
            FATAL("File's magic number was not one of the known options");
        }
    }

    /** Returns true if the file was unmapped correctly, false otherwise. */
    void closeFile() {
        if (munmap(map, size) == -1) {
            perror("Error un-mmapping the file");
        }
        close(fd);
    }
};

#endif