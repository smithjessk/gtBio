#ifndef COMMON_CEL_TYPES
#define COMMON_CEL_TYPES

namespace gtBio {

int32_t fromBEtoSigned(uint8_t* data) {
    // printf("Printing bytes: %02x %02x %02x %02x\n", data[0], data[1], data[2], data[3]);
    return (data[3]<<0) | (data[2]<<8) | (data[1]<<16) | (data[0]<<24);
}

uint32_t fromBEtoUnsigned(uint8_t* data) {
    return (data[3]<<0) | (data[2]<<8) | (data[1]<<16) | (data[0]<<24);
}

float fromBEtoFloat(char* data) {
   float retVal;
   char *floatToConvert = data;
   char *returnFloat = ( char* ) & retVal;

   // swap the bytes into a temporary buffer
   returnFloat[0] = floatToConvert[3];
   returnFloat[1] = floatToConvert[2];
   returnFloat[2] = floatToConvert[1];
   returnFloat[3] = floatToConvert[0];

   return retVal;
}

/**
 * Convert a 16-bit integer (short) from Big Endian to Little Endian
 * @param  data A pointer to where the Big Endian representation begins
 * @return      A short representing the value obtained by swapping the 
 *              Endianness of 16-bit int pointed to by data
 */
int16_t fromBEtoShort(uint8_t* data) {
    return (data[1] << 0) | (data[0] << 8);
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
class WString {
    int32_t size;
    wchar_t* str;

    WString(char* where) {
        size = fromBEtoSigned((uint8_t*)where);
        str = (wchar_t*)((char*) where + (4 * sizeof(uint8_t)));
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

}

#endif