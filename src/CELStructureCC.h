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

void seeBytes(char* input) {
    seeBytes((int8_t)(*input));
    // cout << *(input) << endl;
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

    uint32_t getFirstPosition() {
        return fromBEtoUnsigned(data->firstPosition);
    }

    char* getDataGroupJump(){ 
        return (char*) data + getFirstPosition();
    }

    int32_t getNumGroups(){ return fromBEtoSigned(data->numGroups); }
    
    uint8_t getVersion(){ return data->version; }
};

class GenericDataHeaders {
    struct GenericDataHeader {
        CELCCString dataTypeIdentifier;
        CELCCString uniqueIdentifier; // A GUID
        WString creationTime; // A datetime
        Locale creationOS;
        int32_t numParams; // Name/type/value

        GenericDataHeader(char* where):
        dataTypeIdentifier(where),
        uniqueIdentifier(dataTypeIdentifier.getJump()),
        creationTime(uniqueIdentifier.getJump()),
        creationOS(creationTime.getJump()),
        numParams(fromBEtoSigned((uint8_t*)creationOS.getJump()))
        {}
    };

    // When it was GenericDataHeader* data, a pointer, the underlying constructor
    // for CELCCString was not called, so the values were neither initialized nor read properly.
    // This solution shouldn't be too bad, however, as each GenericDataHeader
    // has minimum overhead and is a nice container. 
    // TODO: Ask Alin about overhead, expansion to include many GenericDataHeader's
    GenericDataHeader data;

public:
    GenericDataHeaders(char* where):
    // creationTime.size is on line 7, second half of second set-of-2 bytes
    // creationTime.getJump() lands you 3 bytes later (two for size, )
    // Strings are not null terminated
    data(where) {
        printf("Unique identifier starts at %p\n", data.dataTypeIdentifier.getJump());
        printf("Creation time starts at %p\n", data.uniqueIdentifier.getJump());
        printf("Creation OS starts at %p\n", data.creationTime.getJump());
        printf("Num params starts at %p\n", data.creationOS.getJump());

        /*seeBytes(where + 4);
        seeBytes(data.creationTime.getJump());
        seeBytes(data.creationTime.getJump() + 1);
        seeBytes(data.creationTime.getJump() + 2);
        seeBytes(data.creationTime.getJump() - 60);*/

        cout << "Data type identifier: " << data.dataTypeIdentifier.str << endl;
        cout << "Unique identifier: " << data.uniqueIdentifier.str << endl;
        cout << "Number of name/value/type parameters: " << data.numParams << endl;

        // @deprecated
        // Note that the creation time is size 0
        // cout << "Creation time: " << data.creationTime.str << endl; // DEPRECATED SOON
        //cout << "Creation OS: " << data.creationOS.getISO3166() << endl;
        // cout << "Number of parameters: " << data.numParams << endl;
    }
};

class CELCommandConsole {
    char* rawData;
    FileHeader fileHeader;
    // GenericDataHeaders gdHeaders;

public:
    CELCommandConsole(char* where):
    rawData(where),
    fileHeader(rawData)
    // gdHeaders(fileHeader.getJump())
    {
        uint32_t nextPosit = fromBEtoUnsigned((uint8_t*)fileHeader.getDataGroupJump());
        cout << "Next Location: " << nextPosit << endl;
    }
};
