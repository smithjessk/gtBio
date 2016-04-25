library(gtBio)

f <- "../../../../demoData/command-console/GSM1134065_GBX.DISC.PCA2.CEL"
data <- ReadCEL(c(f))
x <- View(data, mean = bio::MatrixMean(Intensity))

test_that("mean was correctly computed", {
  expect_equal(x$content, 387.1264)
})