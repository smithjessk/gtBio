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
        CELCCString uniqueIdentifier; // A GUID
        WString creationTime; // A datetime
        Locale creationOS;

        GenericDataHeader(char* where):
        dataTypeIdentifier(where),
        uniqueIdentifier(dataTypeIdentifier.getJump()),
        creationTime(uniqueIdentifier.getJump()),
        creationOS(creationTime.getJump())
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
    data(where) {
        cout << "Data type identifier: " << data.dataTypeIdentifier.str << endl;
        cout << "Unique identifier: " << data.uniqueIdentifier.str << endl;
        /*cout << "Creation Time: " << data.creationTime.str << endl; // TODO: Why is this zero? Is this okay?
        cout << "Creation OS: " << data.creationOS.str << endl;*/
    }
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
