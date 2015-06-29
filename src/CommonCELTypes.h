using namespace std;

#include <cinttypes>

#ifndef COMMON_CEL_TYPES
#define COMMON_CEL_TYPES

int32_t fromBEtoSigned(uint8_t* data) {
    // printf("Printing bytes: %02x %02x %02x %02x\n", data[0], data[1], data[2], data[3]);
    return (data[3]<<0) | (data[2]<<8) | (data[1]<<16) | (data[0]<<24);
}

uint32_t fromBEtoUnsigned(uint8_t* data) {
    return (data[3]<<0) | (data[2]<<8) | (data[1]<<16) | (data[0]<<24);
}

void printFourBytes(uint8_t* data) {
    printf("Printing four bytes: %02x %02x %02x %02x\n", data[0], data[1], data[2], data[3]);
}

/* Need separate CEL string types for V4 and CC because of byte order */
struct CELV4String {
    int32_t size;
    char* str;

    // Constructor from binary layout
    CELV4String(char* where) {
        size = *((int32_t*) where);
        str = (char*) where + sizeof(int32_t);
    }

    char *getJump() { return (char*)str + size; }
};


struct CELCCString {
    int32_t size;
    char* str;

    // TODO: How does this know when to stop printing out?
    CELCCString(char* where) {
        // cout << "Now printing where: " << (void*)where << endl;
        size = fromBEtoSigned((uint8_t*) where);
        str = (char*) where + sizeof(int32_t);
        // cout << "Now printing str: " << sizeof(int32_t) << endl;
    }

    // int32_t getSize() { return fromBEtoSigned(size); }
    char* getJump() { return (char*)str + size; }
};


// Unicode String 
// TODO: getString method that goes from char to wchar_t
struct WString {
    int32_t size;
    char* str;

    WString(char* where) {
        // cout << "Now printing where: " << (void*)where << endl;
        size = fromBEtoSigned((uint8_t*)where);
        str = (char*) where + sizeof(int32_t);
        // cout << "Now printing str: " << str << endl;
    }

    char* getJump() { return (char*)str + size; }
};

// Two character code using ISO
struct Locale {
    char* where;

    // TODO: Why is this not printing? 
    // TODO: FIX THIS
    Locale(char* where) {
        where = where;
    }

    // TODO: Are these two-byte characters or one-byte characters?
    // char getISO369() { return *(ISO369);  }
    // char getISO3166() { return *(ISO3166); }
    char *getJump() { return where + (4 * sizeof(char)); }
};

#endif
