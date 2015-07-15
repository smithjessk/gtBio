/**
 * An abstract class that acts as an interface for the data in CEL files.
 */

#ifndef CEL_BASE_CLASS
#define CEL_BASE_CLASS

#include <armadillo>
#include <memory>

class CELBase {
public:
    using fmat = arma::fmat;
    using pointer = std::unique_ptr<CELBase>;

    /**
     * Get the magic number that identifies the file format
     * @return An integer representing the following:
     *         59 -> Command Console (Generic)
     *         64 -> Version 4
     */
    virtual int32_t getMagic() = 0;

    /**
     * Get the matrix representing the intensity measurements in the CEL file
     * @return A square float matrix representing the intensity measurements
     */
    virtual fmat getIntensityMatrix() = 0;
};

#endif 