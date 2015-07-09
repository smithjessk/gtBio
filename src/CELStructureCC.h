/*
* Can load Affymetix CEL GeneChip Command Console Generic data files.
*/

#include <cinttypes>
#include <stdio.h>
#include <vector>
#include <assert.h>

#include "CommonCELTypes.h"

using namespace std;

void seeBytes(int8_t input) {
    printf("%02X\n", input);
    return;
}

void seeBytes(char* input) {
    seeBytes((int8_t)(*input));
    return;
}

void printUnicodeBytes(char* start, int size) {
    for(int j = 0; j < size; j++)
        printf("%02X ", start[j]);
    cout << endl;
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
        // cout << "Position of first group: " << getFirstPosition() << endl;
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
        uint8_t nameSize[4]; // Signed
    };

    DataGroupData* data;

public:
    DataGroup(char* where):
    data((DataGroupData*) where) 
    {
        printUnicodeBytes((char*) data->nameSize + 4, 2 * fromBEtoSigned(data->nameSize));
    }    
    
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

    vector<uint32_t> getFirstDSPositions() {
        vector<uint32_t> dsPositions (groups.size());
        for (int i = 0; i < groups.size(); i++) {
            dsPositions[i] = groups[i].getFirstDSPosition();
        }
        return dsPositions;
    }
};

// TODO: TEST. This is slightly hairbrained.
class NVTParam {
    uint8_t* nameSize; // Signed
    uint8_t* valueSize; // Signed
    uint8_t* typeSize; // Signed

public:
    NVTParam(char* where):
    nameSize((uint8_t*) where),
    valueSize((uint8_t*) (where + 4 + 2 * (fromBEtoSigned(nameSize)))),
    typeSize((uint8_t*) (valueSize + 4 + 2 *(fromBEtoSigned(valueSize))))
    {}

    char* getJump() {
        return (char*) (typeSize + 4);
    }
};

class NVTParams {
    char* start;
    vector<NVTParam> params;

public:
    NVTParams(char* where, uint8_t* numParams) {
        start = where;
        if (fromBEtoSigned(numParams) > 0) { 
            params.push_back(NVTParam((char*) (numParams + 4)));
        }
        for (int i = 1; i < fromBEtoSigned(numParams); i++) {
            params.push_back(NVTParam(params[i - 1].getJump()));
        }
    }

    char* getJump() {
        if (params.size() > 0) {
            return params.back().getJump();
        } else {
            return start;
        }
    }
};

// WString, Byte, Int representing (Name, Type, and Size)
class Column {
    uint8_t* strSize; // Signed 32-bit int
    uint8_t* value; // 1 byte value
    uint8_t* valSize; // Signed 32-bit int

public:
    
    Column(char* where):
    strSize((uint8_t*) where),
    value(strSize + 4 + 2 * fromBEtoSigned(strSize)),
    valSize(value + 1) {
        // printUnicodeBytes((char*) (strSize) + 4, 2 * fromBEtoSigned(strSize));
        /*cout << "String size: " << fromBEtoSigned(strSize) << endl;
        cout << "Value: " << unsigned(*value) << endl; // TODO: Enum?
        cout << "Value size: " << fromBEtoSigned(valSize) << endl;*/
    }

    int32_t getValSize() {
        return fromBEtoSigned(valSize);
    }

    char* getJump() {
        return (char*) (valSize + 4);
    }
};

class Columns {
    vector<Column> cols;

public:
    Columns(char* where, uint32_t numCols) {
        //cout << "Num cols: " << numCols << endl;
        cols.push_back(Column(where));
        for (int i = 1; i < numCols; i++) {
            cols.push_back(Column(cols[i - 1].getJump()));
        }
    }

    int32_t getRowSize() {
        int32_t size = 0;
        for (int i = 0; i < cols.size(); i++) {
            size += cols[i].getValSize();
        }
        return size;
    }

    char *getJump() {
        return cols.back().getJump();
    }
};

class DataSet {
    // For now we are not going to keep a lot of the header information
    struct DataSetHeader {
        uint8_t firstElemPosition[4]; // Unsigned
        uint8_t nextDataSetPosition[4]; // Unsigned
        uint8_t nameSize[4]; // Signed
    };

    DataSetHeader* dsHeader;
    uint8_t* numParams; // Signed 32-bit. Not in the struct because it's not next to nameSize
    NVTParams params;
    uint8_t* numCols; // Unsigned 32-bit
    Columns cols;
    uint8_t* numRows; // Unsigned 32-bit
    char* dataStart;
    // Rows rows;

public:
    DataSet(char* where):
        dsHeader((DataSetHeader*) where),
        numParams((uint8_t*) (where + 12 + (2 * fromBEtoSigned(dsHeader->nameSize)))),
        params((char*) (numParams + 4), numParams),
        numCols((uint8_t*) params.getJump()),
        cols((char*) (numCols + 4), fromBEtoUnsigned(numCols)),
        numRows((uint8_t*) cols.getJump()),
        dataStart((char*) (numRows + 4)) {
            cout << fromBEtoUnsigned(dsHeader->nextDataSetPosition) << endl;
            //printf("Starting pointer: %p\n", where);

            /*
            uint32_t firstPosit = fromBEtoUnsigned(dsHeader->firstElemPosition);
            // cout << "First elem position: " << firstPosit << endl;
            printf("Resulting pointer: %p\n", where + firstPosit);*/

            char* other = (char*) (numRows+ 4);
            printf("Other pointer: %p\n", other);

            /*printUnicodeBytes((char*) (dsHeader->nameSize) + 4, 2 * fromBEtoSigned(dsHeader->nameSize));
            cout << "Num rows: " << fromBEtoUnsigned(numRows) << endl;
            cout << "First elem position: " << fromBEtoUnsigned(dsHeader->firstElemPosition) << endl;*/
            //cout << "Next data set position: " << fromBEtoUnsigned(dsHeader->nextDataSetPosition) << endl;
            // cout << "Row size in bytes: " << cols.getRowSize() << endl;
            cout << "Printing first 10 float values: " << endl;
            char *val = dataStart;
            for (int i = 0; i < 10; i++) {
                //printf("ptr: %p\n", val);
                //cout << *((float*) val) << endl;
                val = val + cols.getRowSize();
            }
            // cout << "Last value: " << endl;
            val = (dataStart + cols.getRowSize() * (fromBEtoUnsigned(numRows) - 1) );
            printf("ptr: %p\n", val);
            // cout << *((float*) val) << endl;
            char* expl = val + cols.getRowSize();
        }

    char *getJump() {
        //printf("jump: %p\n", dataStart + (fromBEtoUnsigned(numRows) * cols.getRowSize()));
        //printf("jump: %p\n", dataStart + fromBEtoUnsigned(dsHeader->nextDataSetPosition));
        return dataStart + (fromBEtoUnsigned(numRows) * cols.getRowSize());
        // return dataStart + fromBEtoUnsigned(dsHeader->nextDataSetPosition);
    }
};

class DataSetsForGroup {
    vector<DataSet> dSets;

public:
    DataSetsForGroup(char* where, int32_t numDataSets) {
        //cout << "Number of data sets: " << numDataSets << endl;
        // printf("Initial location: %p\n", where);
        char* dsLocation = where;
        for (int i = 0; i < numDataSets; i++) {
            dSets.push_back(DataSet(dsLocation));
            dsLocation = dSets.back().getJump();
            printf("dsLocation: %p\n", dSets.back().getJump());
        }
        // cout << fromBEtoUnsigned((uint8_t*) dsLocation) << endl;
    }
};

// TODO: Add vector of DataSetsForGroup (as opposed to hard coding getGroup(0))
class CELCommandConsole {
    char* rawData;
    FileHeader fileHeader;
    // GenericDataHeaders gdHeaders;
    // DataGroup dataGroup;
    // WString groupName;
    DataGroups dataGroups;
    DataSetsForGroup dataSets;

public:
    CELCommandConsole(char* where):
    rawData(where),
    fileHeader(rawData),
    // gdHeaders(fileHeader.getJump())
    dataGroups(fileHeader.getDataGroupJump()),
    dataSets((where + dataGroups.getGroup(0).getFirstDSPosition()), dataGroups.getGroup(0).getNumDataSets())
    {}
};
