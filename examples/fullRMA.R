library(gtBio)
library(gtBase)

f <- "test.csv"

data <- ReadCSV(f, c(Row = base::int, Data = statistics::vector(size = 5, 
  type = base::int)), header=FALSE)

state <- Collect(data, c(Row, Data), Matrix, size = 5)

correctedMatrix <- BackgroundCorrect(states = state, outputs = 
  c(corrected = Matrix), shouldTranspose = FALSE)

normalizedMatrix <- MedianPolish(states = correctedMatrix, outputs = 
  c(normalized = Matrix), shouldTranspose = FALSE)

x <- View(normalizedMatrix)