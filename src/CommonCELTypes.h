using namespace std;

#include <cinttypes>

#ifndef COMMON_CEL_TYPES
#define COMMON_CEL_TYPES

int32_t fromBEtoSigned(uint8_t* data) {
    printf("Printing bytes: %02x %02x %02x %02x\n", data[0], data[1], data[2], data[3]);
    return (data[3]<<0) | (data[2]<<8) | (data[1]<<16) | (data[0]<<24);
}

uint32_t fromBEtoUnsigned(uint8_t* data) {
    return (data[3]<<0) | (data[2]<<8) | (data[1]<<16) | (data[0]<<24);
}

void printBytes(uint8_t input) {
    printf("Reading bytes: %02X\n", input);
    return;
}

/* Need separate CEL string types for V4 and CC because of byte order */
struct CELV4String {
    int32_t size;
    char* str;

    // Constructor from binary layout
    CELV4String(char* where) {
        size = *((int32_t*) where);
        cout << "String of size " << size << endl; 
        str = (char*) where + sizeof(int32_t);
    }

    char* getJump(void){ return (char*)str + size; }
};


struct CELCCString {
    // char* sizeLoc; // Points to where the size (32 bit int) should be.
    int32_t size;
    char* str;

    // Constructor from binary layout
    CELCCString(char* where) {
        size = fromBEtoSigned((uint8_t*)where);
        cout << "String of size " << size << endl;
        str = (char*) where + sizeof(int32_t);
    }

    // int32_t getSize() { return fromBEtoSigned(size); }
    char* getJump(void){ return (char*)str + size; }
};

/*
class CELCCString {
    struct CELCCStringData {
        uint8_t* size;
        char* str;
    };

    CELCCStringData* data;

public:
    CELCCString(char* where):
    data((CELCCStringData*) where) {
        cout << "Got here" << endl;
    }

    int32_t getSize() { return fromBEtoSigned(data->size); }
};*/

/*
// Unicode String 
struct WString {
    int32_t size;
    wchar_t* str;

    WString(char* where) {
        size = *((int32_t*) where);
        cout << "String of size " << size << endl;
        str = (wchar_t*) where + sizeof(int32_t);
    }

    char* getJump() { return (char*)str + size; }
};*/

#endif
