#!/bin/bash

Rscript produce_tuples.R
Rscript extract_perfect_match.R
Rscript group_into_matrix.R
