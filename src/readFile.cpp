#include <stdio.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <fcntl.h>
#include <sys/mman.h>
#include <cinttypes>

#include "CELStructureV4.h"
#include "CELStructureCC.h"

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

int readFile(char* map) {
    uint8_t magic = unsigned(*((uint8_t*) map));
    if (magic == 59) {
        CELCommandConsole in(map);
        return 0;
    } else if (magic == 64) {
        CEL4 in(map);
        return 0;
    } else {
        return 1;
    }
}

int main(int argc, char *argv[]) {
    int fd = genFD(argv[1]);
    off_t size = lseek(fd, 0, SEEK_HOLE);
    char* map = genMap(fd, size);
    if (readFile(map) == 1) {
        perror("Could not match given file to any known format");
    }
    if (munmap(map, size) == -1) {
        perror("Error un-mmapping the file");
    }
    close(fd);
    return 0;
}
