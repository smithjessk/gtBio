#ifndef CEL_CC
#define CEL_CC

/*
* Can load Affymetix CEL GeneChip Command Console Generic data files.
*/

#include <stdio.h>
#include <vector>
#include <armadillo>
#include <math.h>
#include <memory>

#include "CommonCELTypes.h"
#include "CELBase.h"

namespace gtBio {

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
    std::cout << std::endl;
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
    data((FileHeaderData*) where) {}

    uint8_t getMagic() { return unsigned(data->magic); }

    int32_t getNumGroups() { return fromBEtoSigned(data->numGroups); }
    
    uint8_t getVersion(){ return data->version; }
    
    uint32_t getFirstPosition() {
        return fromBEtoUnsigned(data->firstPosition);
    }

    char* getJump() {
        return (char*) data + sizeof(FileHeaderData); 
    }

    char* getDataGroupJump(){ 
        return (char*) data + getFirstPosition();
    }
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
    data(where) {}
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
    std::vector<DataGroup> groups;

public:
    DataGroups(char* where) {
        groups.push_back(DataGroup(where));
    }

    DataGroup getGroup(int i) {
        return groups.at(i);
    }

    std::vector<uint32_t> getFirstDSPositions() {
        std::vector<uint32_t> dsPositions (groups.size());
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
    std::vector<NVTParam> params;

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
    valSize(value + 1) {}

    int32_t getValSize() {
        return fromBEtoSigned(valSize);
    }

    char* getJump() {
        return (char*) (valSize + 4);
    }
};

class Columns {
    std::vector<Column> cols;

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

// There are some extra bytes after the end of the data; unknown purpose.
class DataSet {
    // For now we are not going to keep a lot of the header information
    struct DataSetHeader {
        uint8_t firstElemPosition[4]; // Unsigned
        uint8_t nextDataSetPosition[4]; // Unsigned
        uint8_t nameSize[4]; // Signed
    };

    char* origWhere;
    DataSetHeader* dsHeader;
    uint8_t* numParams; // Signed 32-bit. Not in struct b/c not adj to nameSize
    NVTParams params;
    uint8_t* numCols; // Unsigned 32-bit
    Columns cols;
    uint8_t* numRows; // Unsigned 32-bit
    double sqrtOfRows;
    char* dataStart;

public:
    DataSet(char* where, char* origWhere):
        origWhere(origWhere),
        dsHeader((DataSetHeader*) where),
        numParams((uint8_t*) (where + 12 + 
            (2 * fromBEtoSigned(dsHeader->nameSize)))),
        params((char*) (numParams + 4), numParams),
        numCols((uint8_t*) params.getJump()),
        cols((char*) (numCols + 4), fromBEtoUnsigned(numCols)),
        numRows((uint8_t*) cols.getJump()),
        sqrtOfRows(sqrt(fromBEtoUnsigned(numRows))),
        dataStart((char*) (numRows + 4)) {}

    char *getJump() {
        return origWhere + fromBEtoUnsigned(dsHeader->nextDataSetPosition);
    }

    uint32_t getNumRows() {
        return fromBEtoUnsigned(numRows);
    }

    char* getElement(int row, int col) {
        uint32_t blocksFromRows = sqrtOfRows * row;
        return dataStart + cols.getRowSize() * (blocksFromRows + col);
    }

    char* getDataStart() { return dataStart; }
};

class DataSetsForGroup {
    std::vector<DataSet> dSets;

public:
    DataSetsForGroup(char* where, int32_t numDataSets, char* origWhere) {
        char* dsLocation = where;
        // TODO: Correctly work with this for more data sets
        for (int i = 0; i < numDataSets - 1; i++) {
            dSets.push_back(DataSet(dsLocation, origWhere));
            dsLocation = dSets.back().getJump();
        }
    }

    DataSet get(int index) { return dSets.at(index); }
};

// TODO: Add vector of DataSetsForGroup (as opposed to hard coding getGroup(0))
class CELCommandConsole : public CELBase {
    char* rawData;
    FileHeader fileHeader;
    // GenericDataHeaders gdHeaders;
    // DataGroup dataGroup;
    // WString groupName;
    DataGroups dataGroups;
    DataSetsForGroup dataSets;

public:
    using pointer = std::unique_ptr<CELCommandConsole>;

    CELCommandConsole(char* where):
    rawData(where),
    fileHeader(rawData),
    // gdHeaders(fileHeader.getJump())
    dataGroups(fileHeader.getDataGroupJump()),
    dataSets((where + dataGroups.getGroup(0).getFirstDSPosition()),
        dataGroups.getGroup(0).getNumDataSets(), where)
    {}

    int32_t GetMagic() { return fileHeader.getMagic(); }

    // TODO: Update this for multiple data groups
    
    /**
     * Read the intensity values and transform them from little endian to big
     * @return A square matrix in row-major order 
     */
    fmat GetIntensityMatrix() override {
      char* dStart = dataSets.get(0).getDataStart();

      // Square matrix
      uint32_t sideLength = sqrt(dataSets.get(0).getNumRows()); 
      fmat ret((float*) dStart, sideLength, sideLength); 
        
      ret.transform([] (float &val) {
        return fromBEtoFloat((char*) &val); // Big endian to little 
      });

      return ret.t(); // Column-major to row-major
    }

    fmat GetStdDevMatrix() override {
        char* dStart = dataSets.get(1).getDataStart();

        uint32_t sideLength = sqrt(dataSets.get(1).getNumRows());
        fmat ret((float*) dStart, sideLength, sideLength);

        ret.transform([] (float &val) {
            return fromBEtoFloat((char*) &val); // Big endian to little
        });

        return ret.t(); // Column-major to row-major
    }

    imat GetPixelsMatrix() override {
        char* dStart = dataSets.get(2).getDataStart();

        uint32_t sideLength = sqrt(dataSets.get(2).getNumRows());
        arma::Mat<int16_t> temp((int16_t*) dStart, sideLength, sideLength);

        temp.transform([] (int16_t &val) {
            return fromBEtoShort((uint8_t*) &val);
        });

        imat ret(sideLength, sideLength);
        for (int i = 0; i < sideLength; i++) {
            for (int j = 0; j < sideLength; j++) {
                ret(i, j) = temp(i, j);
            }
        }

        return ret.t(); // Column-major to row-major
    }
};

}

#endif // CEL_CC