library(gtBio)

testMatrix <- matrix(c(4, 3, 6, 4, 7,
                       8, 1, 10, 5, 11,
                       6, 2, 7, 8, 8,
                       9, 4, 12, 9, 12,
                       7, 5, 9, 6, 10), nrow = 5, ncol = 5)
normalizedMatrix <- MedianPolish(testMatrix)