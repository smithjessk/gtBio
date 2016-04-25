# gtBio
[![Build Status](https://travis-ci.org/smithjessk/gtBio.svg?branch=master)](https://travis-ci.org/smithjessk/gtBio)

## About

A C++ library that provides extremely fast processing of bioinformatics data.

For information on the directories contained in this project, see the [Package Structure](https://github.com/smithjessk/gtBio/wiki/Package-Structure) page.

## Building
    
#### R Package ####

If you have `sudo` permissions:

``` bash
$ cd gtBio
$ R CMD INSTALL .
$ grokit makelib ../bio
```

To install locally:

```bash
$ cd gtBio
$ R CMD INSTALL -l ~/my/directory .
$ grokit makelib ../bio
```

where `~/my/directory` is a folder R knows to search for libraries.

## Testing 

There are currently unresolved bugs in using `testthat`. So instead, the example scripts serve as tests. If they all execute normally, then `gtBio` is operational. 

The demo data needed for these tests can be found in earlier versions of master. Soon we'll add an automated way to get this data.