library(gtBio)
library(gtBase)

f <- "test.csv"
data <- ReadCSV(f, c(Row = base::int, Data = statistics::vector(size = 5, 
type = base::double)), header=FALSE)
state <- Collect(data, c(Row, Data), Matrix, size = 5)
normalizedMatrix <- BackgroundCorrect(states = state, 
  outputs = c(normalized = Matrix), shouldTranspose = TRUE, 
  field_to_access = "Q1_Matrix", from_matrix = TRUE)
x <- View(normalizedMatrix)