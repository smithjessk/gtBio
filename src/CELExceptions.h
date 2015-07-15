#ifndef CEL_EXCEPTIONS
#define CEL_EXCEPTIONS

#include <stdexcept>
#include <string>

class BadMagicException : public std::runtime_error {
public:
    BadMagicException(const string& message) 
        : std::runtime_error(message) { };
};

#endif