#include "CELFileReader.h"

int main(int argc, char *argv[]) {
    CELFileReader in(argv[1]);
    CELBase::pointer data = in.readFile();
    arma::fmat nums = data.get()->getIntensityMatrix();
    return 0;
}