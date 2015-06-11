#include <cinttypes>
#include <assert.h>

/*
	This file contain classes that form part of Affymatrix CEL V4 binary format

	All classes must have a getJump() function that specifies
	 where to jump to find the next structure in the binary format
 */


class TopHeader {
	struct TopHeaderData {
		int32_t macic; // should be 64
		int32_t version; // should be 4
		int32_t numCols; 
		int32_t numRows;
		int32_t numCells; // should be numCols*numRows
		int32_t headerLength; // this is the CEL v3 header length
	};

	TopHeaderData* data;

public:
	TopHeader(void* where):
		data((TopHeaderData*) where) {}

	void* getJump(void){ return (void*)data + sizeof(TopHeaderData);}
	int32_t getNumRows(void){ return data->numRows; }
	int32_t getNumCols(void){ return data->numCols; }
};

/** Strings as encoded into CEL file. They have an integer size followed by the char[length] */
struct CELString {
	int32_t size; // size
	char* str; // pointer to string. WARNING: This is not \0 terminated
	
	// Constructor from binary layout
	CELString(void* where){
		size = *((int32_t*) where); // layout the int at the begining
		str = ((char*) where) + sizeof(int32_t);
	}

	// how much to jump in the binary layout to get the next header
	void* getJump(void){ return (void*)str + size; }
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
	TopHeader2(void* where):
		data((TopHeader2Data) where) {}

	void* getJump(void){ return (void*) data + sizeof(TopHeader2Data); }	
};


class CellEntries {
	struct CellEntry {
		float intensity;
		float stdDev; 
		int16_t pixels; // how many pixels
	};

	int32_t numRows; 
	int32_t numColumns;
	CellEntry* cells; // the pointer to cells

public: 
	CellEntries(int32_t numRows, int32_t numCols, void* where):
		numRows(numRows), 
		numCols(numCols), 
		cells((CellEntry*) where) {}

	void* getJump(void){ 
		return (void*) cells + 
			numRows*numCols* sizeof(CellEntry); 
	}

	// maybe this should check
	CellEntry getValue(row, col){ 
		assert(row<numRows && col<numCols );
		return cells[row*numCols + col]; 
	}
	float getIntensity(row, col){ return getValue(row,col).intensity; }
	float getStdDev(row, col){ return getValue(row,col).intensity; }
	float getIntensity(row, col){ return getValue(row,col).intensity; }
	
};

class CEL4 {
	void* rawData; // this is the raw data that we were passed when constructed
	TopHeader header; // top header
	CELString cel3Header;
	CELString algorithm;
	CELString algorithmParams;
	TopHeader2 header2; // second header
	CellEntries cells; // the cells in the data

public:
	CEL4(void* where):
		rawData(where),
		header(rawData),
		cel3Header( header.getJump() ),
		algorithm( cel3Header.getJump() ),
		algorithmParams( algorithm.getJump() ),
		header2( algorithmParams.getJump() ),
		cells( header.getNumRows(), header.getNumCols(), header2.getJump())
	{}	 
};

