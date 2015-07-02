/*
* Can load Affymetix CEL GeneChip Command Console Generic data files.
*/

#include <cinttypes>
#include <stdio.h>
#include <vector>

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

/*
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

    GenericDataHeader data;

public:
    GenericDataHeaders(char* where):
    data(where) {
        cout << "Data type identifier: " << data.dataTypeIdentifier.str << endl;
        cout << "Unique identifier: " << data.uniqueIdentifier.str << endl;
        cout << "Number of name/value/type parameters: " << data.numParams << endl;
    }
};*/

// TODO: Add name reader
// TODO: Add data group vector (find number of data groups ahead of time)
class DataGroup {
    struct DataGroupData {
        uint8_t nextPosition[4]; // Unsigned
        uint8_t firstDataSetPosition[4]; // Unsigned
        uint8_t numDataSets[4]; // Signed
    };

    DataGroupData* data;

public:
    DataGroup(char* where):
    data((DataGroupData*) where) 
    {}    
    
    uint32_t getNextPosition() {
        return fromBEtoUnsigned((uint8_t*) data->nextPosition);
    }

    uint32_t getFirstDSPosition() {
        return fromBEtoUnsigned((uint8_t*) data->firstDataSetPosition);
    }

    int32_t getNumDataSets() {
        return fromBEtoSigned((uint8_t*) data->numDataSets);
    }
};

class DataGroups {
    vector<DataGroup> groups;

public:
    DataGroups(char* where) {
        groups.push_back(DataGroup(where));
    }

    DataGroup getGroup(int i) {
        return groups.at(i);
    }
};

class CELCommandConsole {
    char* rawData;
    FileHeader fileHeader;
    // GenericDataHeaders gdHeaders;
    // DataGroup dataGroup;
    // WString groupName;
    DataGroups dataGroups;

public:
    CELCommandConsole(char* where):
    rawData(where),
    fileHeader(rawData),
    // gdHeaders(fileHeader.getJump())
    dataGroups(fileHeader.getDataGroupJump())
    {
        DataGroup dataGroup = dataGroups.getGroup(0);
        cout << "Next Location: " << dataGroup.getNextPosition() << endl;
        cout << "First DS: " << dataGroup.getFirstDSPosition() << endl;
        cout << "Number of data sets: " << dataGroup.getNumDataSets() << endl;
    }
};
