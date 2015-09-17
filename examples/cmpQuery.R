library(gtBio)
library(gtBase)

f <- "../demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL"
data <- ReadCEL(c(f))
normalizedMatrix <- MedianPolish(states = data, outputs = 
  c(normalized = Matrix), shouldTranspose = FALSE, fieldToAccess = "Intensity")
x <- View(normalizedMatrix)