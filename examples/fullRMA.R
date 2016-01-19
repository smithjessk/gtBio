library(gtBio)
library(gtBase)

file <- "../demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL"
matrix <- BuildMatrix(c(file))
corrected <- RMA(matrix)
x <- as.object(corrected)
