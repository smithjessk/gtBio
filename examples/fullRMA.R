library(gtBio)
library(gtBase)

celFile <- 
  "/home/jess/git/gtBio/demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL"
infoFile <- "/home/jess/git/gtBio/scripts/pd.huex.1.0.st.v2.csv"

files <- c(celFile)

# Joins the input and creates the matrices for background correct
matrix <- BuildMatrix(files, infoFile)

corrected <- BackgroundCorrect(states = matrix, outputs = 
    c(corrected = Matrix), should_transpose = FALSE, from_matrix = TRUE)

normalized <- QuantileNormalize(
           states = corrected,
           outputs = c(file_name, ordered_fid, intensity),
           should_transpose = FALSE,
           files = files)

info <- ReadPMInfoFile(infoFile)
joined <- Join(normalized, ordered_fid, info, ordered_fid)
output <- GroupBy(
       joined,
       ordered_fsetid,
       MedianPolish())

x <- as.object(Count(output))