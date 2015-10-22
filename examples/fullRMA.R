library(gtBio)
library(gtBase)

# f <- "test.csv"
#data <- ReadCSV(f, c(Row = base::int, Data = statistics::vector(size = 5, 
# type = base::int)), header=FALSE)

# state <- Collect(data, c(Row, Data), Matrix, size = 5)

f <- "../demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL"
data <- ReadCEL(c(f))
state <- Gather(data)

correctedMatrix <- BackgroundCorrect(states = state, outputs = 
  c(corrected = Matrix), shouldTranspose = FALSE, 
  field_to_access = "Q1__Intensity")

normalizedMatrix <- MedianPolish(states = correctedMatrix, outputs = 
  c(normalized = Matrix), shouldTranspose = FALSE)

x <- View(normalizedMatrix)