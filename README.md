# gtBio
[![Build Status](https://travis-ci.org/smithjessk/gtBio.svg?branch=master)](https://travis-ci.org/smithjessk/gtBio)

## About

A C++ library that provides extremely fast processing of bioinformatics data.

For information on the directories contained in this project, see the [Package Structure](https://github.com/smithjessk/gtBio/wiki/Package-Structure) page.

## Building

Note that this requires the Google Testing framework. For a sample way to install this, see the `.travis.yml` section "before_script."

#### C++ Library ####

    $ make core # To build the core C++ library
    $ make tests # To build the google tests
    
#### R Package ####

    $ cd gtBio
    $ R CMD INSTALL .
