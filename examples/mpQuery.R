library(gtBio)
library(gtBase)

f <- "test.csv"
data <- ReadCSV(f, c(Row = base::int, Data = statistics::vector(size = 5, 
  type = base::int)), header=FALSE)
state <- Collect(data, c(Row, Data), Matrix, size = 5)
normalizedMatrix <- MedianPolish(states = state, outputs = 
  c(normalized = Matrix))
x <- View(data)