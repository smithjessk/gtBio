/**
 * An abstract class that acts as an interface for the data in CEL files.
 */

#ifndef CEL_BASE_CLASS
#define CEL_BASE_CLASS

#include <armadillo>
#include <memory>
#include <cinttypes>

namespace gtBio {

class CELBase {
public:
    using fmat = arma::fmat;
    using imat = arma::Mat<int32_t>;
    using pointer = std::unique_ptr<CELBase>;

    /**
     * Get the magic number that identifies the file format
     * @return An integer representing the following:
     *         59 -> Command Console (Generic)
     *         64 -> Version 4
     */
    virtual int32_t getMagic() = 0;

    /**
     * Get the matrix representing the intensity measurements in the CEL file.
     * Enntry (i, j) in this matrix corresponds to entry (i, j) in the result 
     * of getStdDevMatrix()
     * @return A square float matrix representing the intensity measurements
     */
    virtual fmat getIntensityMatrix() = 0;

    /**
     * Get the matrix representing the standard deviations of the intensity 
     * measurements in the CEL file. Entry (i, j) in this matrix corresponds to
     * entry (i, j) in the result of getIntensityMatrix()
     * @return A sqaure float matrix representing the standard deviations of 
     * the intensity measurements.
     */
    virtual fmat getStdDevMatrix() = 0;

    /**
     * Get the matrix representing the number of pixels for each entry in the
     * CEL file. Entry (i, j) in this matrix corresponds to entry (i, j) in
     * the result of getIntensityMatrix() and getStdDevMatrix()
     * @return A sqaure short matrix representing number of pixels for each 
     * entry in the other two matrices (intensity and standard deviation)
     */
    virtual imat getPixelsMatrix() = 0;
};

}

#endif 
