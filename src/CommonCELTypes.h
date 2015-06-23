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
        size = fromBEtoSigned((uint8_t*) where);
        str = (char*) where + sizeof(int32_t);
    }

    // int32_t getSize() { return fromBEtoSigned(size); }
    char* getJump() { return (char*)str + size; }
};


// Unicode String 
struct WString {
    int32_t size;
    wchar_t* str;

    WString(char* where) {
        size = fromBEtoSigned((uint8_t*)where);
        str = (wchar_t*) where + sizeof(int32_t);
    }

    char* getJump() { return (char*)str + size; }
};

// Two character code using ISO
struct Locale {
    char* str;

    // TODO: Why is this not printing? 
    Locale(char* where) {
        str = where;
    }

    char *getJump() { return (char*)str + 2; }
};

#endif
