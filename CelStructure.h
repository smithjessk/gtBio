#include <cinttypes>
#include <assert.h>
#include <iostream>

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
			cout << "Magic: " << data->magic << endl;
			cout << "Version: " << data->version << endl;
			cout << "numCols: " << data->numCols << endl;
			cout << "numRows: " << data->numRows << endl;
		}

	char* getJump(void){ return (char*)data + sizeof(TopHeaderData);}
	int32_t getNumRows(void){ return data->numRows; }
	int32_t getNumCols(void){ return data->numCols; }
};

/** Strings as encoded into CEL file. They have an integer size followed by the char[length] */
struct CELString {
	int32_t size; // size
	char* str; // pointer to string. WARNING: This is not \0 terminated
	
	// Constructor from binary layout
	CELString(char* where){
		size = *((int32_t*) where); // layout the int at the begining
		cout << "String of size " << size << endl; 
		str = (char*) where + sizeof(int32_t);
	}

	// how much to jump in the binary layout to get the next header
	char* getJump(void){ return (char*)str + size; }
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
			cout << "Margin: " << data->margin << endl;
			cout << "numOutliers: " << data->numOutliers << endl;
			cout << "numMaskedCells: " << data->numMaskedCells << endl;
			cout << "numSubgrids: " << data->numSubgrids << endl;
		}

	char* getJump(void){ return (char*) data + sizeof(TopHeader2Data); }	
};


class CellEntries {
	struct CellEntry {
		float intensity;
		float stdDev; 
		int16_t pixels; // how many pixels
	};

	int32_t numRows; 
	int32_t numCols;
	CellEntry* cells; // the pointer to cells

public: 
	CellEntries(int32_t numRows, int32_t numCols, char* where):
		numRows(numRows), 
		numCols(numCols), 
		cells((CellEntry*) where) {}

	char* getJump(void){ 
		return (char*) cells + numRows*numCols* sizeof(CellEntry);
	}

	// maybe this should check
	CellEntry getValue(int row, int col){ 
		assert(row < numRows && col < numCols );
		return cells[row*numCols + col]; 
	}

	float getIntensity(int row, int col){ return getValue(row,col).intensity; }
	float getStdDev(int row, int col){ return getValue(row,col).intensity; }
	float getPixels(int row, int col){ return getValue(row,col).pixels; }
	
};

class CEL4 {
	char* rawData; // this is the raw data that we were passed when constructed
	TopHeader header; // top header
	CELString cel3Header;
	CELString algorithm;
	CELString algorithmParams;
	TopHeader2 header2; // second header
	CellEntries cells; // the cells in the data

public:
	CEL4(char* where):
		rawData(where),
		header(rawData),
		cel3Header( header.getJump() ),
		algorithm( cel3Header.getJump() ),
		algorithmParams( algorithm.getJump() ),
		header2( algorithmParams.getJump() ),
		cells( header.getNumRows(), header.getNumCols(), header2.getJump())
	{}	
};