<?
function Median_Polish($t_args, $outputs, $states) {
    $className = generate_name('Median_Polish');
    $matrix = array_keys($states)[0];
    $matrixType = array_values($states)[0];
    $innerType = $matrixType->get('type');
    $shouldTranspose = get_default($t_args, 'shouldTranspose', False);
    $output = ['polished_matrix' => lookupType('bio::Variable_Matrix', 
      ['type' => $innerType])];

    $identifier = [
        'kind'  => 'GIST',
        'name'  => $className,
        'system_headers' => ['armadillo', 'algorithm'],
        'libraries'     => ['armadillo'],
        'iterable'      => true,
        'output'        => $output,
        'result_type'   => 'single',
    ];
?>

class ConvergenceGLA {
 public:
  int roundNum;
  bool convergingThisRound;

  ConvergenceGLA(int num) :
      roundNum(num),
      convergingThisRound(true) {}

  void AddState(ConvergenceGLA other) {
    convergingThisRound = convergingThisRound && other.convergingThisRound;
  }

  // TODO: Add better converging conditions
  bool ShouldIterate() {
    return roundNum < 5;
  }
};

class <?=$className?> {
 public:
  // We don't know what type of matrix we will be passed, so it is best to be
  // type-agnostic.
  using InnerType = <?=$innerType?>;
  using Matrix = <?=$matrixType?>::Matrix;
  using cGLA = ConvergenceGLA;

  struct Task {
    long startIndex; // Which row or column this local scheduler starts at
    long endIndex; // Which row/col it ends at. Inclusive bound.
  };

  struct LocalScheduler {
    int threadIndex;
    bool finishedScheduling;
    int numThreads;
    int &roundNum;
    Matrix &matrix;

    LocalScheduler(int index, int &roundNum, int numThreads, Matrix &matrix) :
        threadIndex(index),
        finishedScheduling(false),
        numThreads(numThreads),
        roundNum(roundNum),
        matrix(matrix) {}

    bool GetNextTask(Task& task) {
      bool ret = !finishedScheduling;
      long count;
      if (roundNum % 2 == 1) {
        count = matrix.n_rows;
      } else {
        count = matrix.n_cols;
      }
      task.startIndex = threadIndex * count / numThreads;
      task.endIndex = (threadIndex + 1) * count / numThreads - 1;
      finishedScheduling = true;
      return ret;
    }
  };

  using WorkUnit = std::pair<LocalScheduler*, cGLA*>;
  using WorkUnits = std::vector<WorkUnit>;

 private:
  // Without a limit on the number of iterations, the process could never end.
  int roundNum;
  int numThreads;
  Matrix matrix;

  void RowPolish(Task& task, cGLA& gla) {
    int start = task.startIndex;
    int end = task.endIndex;
    /*std::cout << "Num rows: " << temp.n_rows << std::endl;
    std::cout << "Num cols: " << temp.n_cols << std::endl;
    printf("Entries: %d, %d, %d, %d, %d", temp(0, 0), temp(0, 1), temp(0, 2), temp(0, 3), temp(0, 4));*/
    arma::Col<InnerType> medVal = 
      median(matrix.submat(start, 0, end, matrix.n_cols - 1), 1);
    printf("Row polish with start %d and end %d has median value %d\n", start, end, medVal(0, 0));
    arma::Col<InnerType> med(matrix.n_cols);
    med.fill(medVal(0, 0));
    matrix.submat(start, 0, end, matrix.n_cols - 1) -= med.t();
    for (int i = start; i <= end; i++) {
      for (int j = 0; j <= matrix.n_cols - 1; j++) {
        printf("Entry %d, %d is %d\n", i, j, matrix(i, j));
      }
    }
  }

  void ColPolish(Task& task, cGLA& gla) {
    int start = task.startIndex;
    int end = task.endIndex;
    arma::Col<InnerType> medVal = 
      median(matrix.submat(0, start, matrix.n_rows - 1, end), 0);
    printf("Col polish with start %d and end %d has median value %d\n", start, end, medVal(0, 0));
    arma::Col<InnerType> med(matrix.n_rows);
    med.fill(medVal(0, 0));
    matrix.submat(0, start, matrix.n_rows - 1, end) -= med;
    for (int i = 0; i <= matrix.n_rows - 1; i++) {
      for (int j = start; j <= end; j++) {
        printf("Entry %d, %d is %d\n", i, j, matrix(i, j));
      }
    }
  }

 public:
  <?=$className?>(<?=const_typed_ref_args($states)?>) {

<? if ($shouldTranspose) { ?>
        std::cout << "Transposing matrix..." << std::endl;
        matrix = <?=$matrix?>.GetMatrix().t();
<? } else { ?>
        matrix = <?=$matrix?>.GetMatrix();
<? } ?> 
        roundNum = 0;
      }

  // Advance the round number and distribute work among the threads
  void PrepareRound(WorkUnits& workers, int numThreads) {
    roundNum++;
    arma::uword n_rows = matrix.n_rows;
    arma::uword n_cols = matrix.n_cols;
    int minDimension = std::min(n_rows, n_cols);
    this->numThreads = std::min(minDimension, numThreads);
    std::cout << "Beginning round " << roundNum << " with " << this->numThreads
      << " workers." << std::endl;
    std::pair<LocalScheduler*, cGLA*> worker;
    for (int counter = 0; counter < this->numThreads; counter++) {
      worker = std::make_pair(new LocalScheduler(counter, roundNum, 
        this->numThreads, matrix), new cGLA(roundNum));
      workers.push_back(worker);
    }
  }

  // If round number is odd, do a row polish. Otherwise, do a column polish.
  void DoStep(Task& task, cGLA& gla) {
    if (roundNum % 2 == 1) {
      RowPolish(task, gla);
    } else {
      ColPolish(task, gla);
    }
  }

  void GetResult(<?=typed_ref_args($output)?>) {
    polished_matrix = matrix;
  }
};

<?
    return $identifier;
}
?>
