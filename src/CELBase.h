/**
 * An abstract class that acts as an interface for the data in CEL files.
 */

#ifndef CEL_BASE_CLASS
#define CEL_BASE_CLASS

#include <armadillo>

using namespace arma;

class CELBase {
public:
    /**
     * Get the magic number that identifies the file format
     * @return An integer representing the following:
     *         59 -> Command Console (Generic)
     *         64 -> Version 4
     */
    virtual uint8_t getMagic() = 0;

    /**
     * Get the matrix representing the intensity measurements in the CEL file
     * @return A square float matrix representing the intensity measurements
     */
    virtual mat getIntensityMatrix() = 0;
};

#endif 