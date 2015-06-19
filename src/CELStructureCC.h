/*
* Can load Affymetix CEL GeneChip Command Console Generic data files.
*/

#include <cinttypes>
#include <stdio.h>

#include "CommonCELTypes.h"

using namespace std;

void seeBytes(int8_t input) {
    printf("%02X\n", input);
    return;
}

class FileHeader {
    struct FileHeaderData {
        uint8_t magic; // Should be 59
        uint8_t version; // Should be 1
        uint8_t numGroups[4]; // raw number of groups
        uint8_t firstPosition[4]; // Position of first data group
    };

    FileHeaderData* data;

public:
    FileHeader(char* where):
    data((FileHeaderData*) where) {
        cout << "Magic: " << unsigned(data->magic) << endl;
        cout << "Version: " << unsigned(data->version) << endl;
        cout << "Number of groups: " << getNumGroups() << endl;
        cout << "Position of first group: " << getFirstPosition() << endl;
    }

    char* getJump() {
        return (char*) data + sizeof(FileHeaderData); 
    }

    int32_t getNumGroups(){ return fromBEtoSigned(data->numGroups); }
    uint32_t getFirstPosition(){ return fromBEtoUnsigned(data->firstPosition) ;}
    uint8_t getVersion(){ return data->version; }
};

class GenericDataHeaders {
    struct GenericDataHeader {
        CELCCString dataTypeIdentifier;
        // CELCCString uniqueIdentifier; // A GUID
        // WString created;
    };

    GenericDataHeader* data;

public:
    GenericDataHeaders(char* where):
    data((GenericDataHeader*) where) {
        cout << "What the size is: " << data->dataTypeIdentifier.size << endl;            
    }
    /*
    data() {
        // data->dataTypeIdentifier.size = fromBEtoSigned((uint8_t*)where);
        cout << "What it should be: " << fromBEtoSigned((uint8_t*)where) << endl;
        int32_t size = (data->dataTypeIdentifier.size);
        cout << "Size of data type ident: " << size << endl;
    }*/
};

class CELCommandConsole {
    char* rawData;
    FileHeader fileHeader;
    GenericDataHeaders gdHeaders;

public:
    CELCommandConsole(char* where):
    rawData(where),
    fileHeader(rawData),
    gdHeaders(fileHeader.getJump())
    {}
};
