library(gtBio)
library(gtBase)

f <- "../demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL"
data <- ReadCEL(c(f))
state <- Gather(data)
normalizedMatrix <- BackgroundCorrect(states = state, 
  outputs = c(normalized = Matrix), shouldTranspose = FALSE, 
  field_to_access = "Q1__Intensity")
x <- View(normalizedMatrix)