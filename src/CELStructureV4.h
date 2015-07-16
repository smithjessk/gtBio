#include <cinttypes>
#include <assert.h>
#include <iostream>

#include "CommonCELTypes.h"

using namespace std;

/*
This file contain classes that form part of Affymatrix CEL V4 binary format

All classes must have a getJump() function that specifies
where to jump to find the next structure in the binary format
*/

class TopHeader {
    struct TopHeaderData {
        int32_t magic; // should be 64
        int32_t version; // should be 4
        int32_t numCols; 
        int32_t numRows;
        int32_t numCells; // should be numCols*numRows
    };

    TopHeaderData* data;

public:
    TopHeader(char* where):
    data((TopHeaderData*) where) {
        /*&cout << "Magic: " << data->magic << endl;
        cout << "Version: " << data->version << endl;
        cout << "numCols: " << data->numCols << endl;
        cout << "numRows: " << data->numRows << endl;*/
    }

    char* getJump(void){ return (char*)data + sizeof(TopHeaderData);}
    int32_t getNumRows(void){ return data->numRows; }
    int32_t getNumCols(void){ return data->numCols; }
    int32_t getMagic() { return data->magic; }
};

class TopHeader2 {
    struct TopHeader2Data {
        int32_t margin; // cell margin used for computign the cell intensity value
        uint32_t numOutliers; // Number of outlier cells.
        uint32_t numMaskedCells; // Number of masked cells.
        int32_t	numSubgrids;
    };

    TopHeader2Data* data;

public:
    TopHeader2(char* where):
    data((TopHeader2Data*) where) {
        /*cout << "Margin: " << data->margin << endl;
        cout << "numOutliers: " << data->numOutliers << endl;
        cout << "numMaskedCells: " << data->numMaskedCells << endl;
        cout << "numSubgrids: " << data->numSubgrids << endl;*/
    }

    char* getJump(void){ return (char*) data + sizeof(TopHeader2Data); }	
};

class CellEntries {
    struct CellEntry {
        float intensity;
        float stdDev; 
        int16_t pixels; // How many pixels
    };

    int32_t numRows;
    int32_t numCols;
    char* cells; // the pointer to cells

public: 
    CellEntries(int32_t numRows, int32_t numCols, char* where):
    numRows(numRows), 
    numCols(numCols), 
    cells(where) {}

    char* getJump(void){ 
        return (char*) cells + numRows*numCols* sizeof(CellEntry);
    }
    
    char* getJumpToCellEntry(int row, int col) {
        int rowJump = row * (10 * numCols);
        int colJump = 10 * col;
        return cells + rowJump + colJump;
    }

    float getIntensity(int row, int col) {
        float *intensity = (float*) getJumpToCellEntry(row, col);
        return *intensity;
    }

    float getStdDev(int row, int col) {
        float *stdDev = (float*) (getJumpToCellEntry(row, col) + 4);
        return *stdDev;
    }

    int16_t getPixels(int row, int col) {
        int16_t *pixels = (int16_t*) (getJumpToCellEntry(row, col) + 8);
        return *pixels;
    }
};

class CEL4 : public CELBase {
    char* rawData;
    TopHeader header;
    CELV4String cel3Header;
    CELV4String algorithm;
    CELV4String algorithmParams;
    TopHeader2 header2; // second header
    CellEntries cells;

public:
    using pointer = std::unique_ptr<CEL4>;

    CEL4(char* where):
    rawData(where),
    header(rawData),
    cel3Header( header.getJump() ),
    algorithm( cel3Header.getJump() ),
    algorithmParams( algorithm.getJump() ),
    header2( algorithmParams.getJump() ),
    cells( header.getNumRows(), header.getNumCols(), header2.getJump())
    {}	

    int32_t getMagic() override {
        return header.getMagic();
    }

    arma::fmat getIntensityMatrix() override {
        int32_t numRows = header.getNumRows();
        int32_t numCols = header.getNumCols();
        arma::fmat ret(numRows, numCols);
        for (int i = 0; i < numRows; i++) {
            for (int j = 0; j < numCols; j++) {
                ret(i, j) = cells.getIntensity(i, j);
            }
        }
        return ret;
    }

    arma::fmat getStdDevMatrix() override {
        int32_t numRows = header.getNumRows();
        int32_t numCols = header.getNumCols();
        arma::fmat ret(numRows, numCols);
        for (int i = 0; i < numRows; i++) {
            for (int j = 0; j < numCols; j++) {
                ret(i, j) = cells.getStdDev(i, j);
            }
        }
        return ret;
    }
};
