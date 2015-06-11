#include <stdio.h>
#include <stdlib.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <fcntl.h>
#include <sys/mman.h>
#include <cinttypes>
#include "CelStructure.h"

#define NUMINTS  (1)
#define FILESIZE (NUMINTS * sizeof(int32_t))

int main(int argc, char *argv[]) {
    char *map;

    int fd = open(argv[1], O_RDONLY);
    if (fd == -1) {
        perror("Error opening file for reading");
        exit(EXIT_FAILURE);
    }
    off_t size = lseek(fd, 0, SEEK_HOLE);

    map = (char *) mmap(NULL, size, PROT_READ, MAP_SHARED, fd, 0);
    if (map == MAP_FAILED) {
       close(fd);
       perror("Error mmapping the file");
       exit(EXIT_FAILURE);
    }
    
    CEL4 in(map);


    if (munmap(map, FILESIZE) == -1) {
        perror("Error un-mmapping the file");
    }
    
    close(fd);
    return 0;
}