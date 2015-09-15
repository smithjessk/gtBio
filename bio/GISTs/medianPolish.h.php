<?
function Median_Polish($t_args, $outputs, $states) {
    $className = generate_name('Median_Polish');
    $matrix = array_keys($states)[0];
    $matrixType = array_values($states)[0];
    $innerType = $matrixType->get('type');
    $output = ['polished_matrix' => lookupType('bio::Variable_Matrix', 
      ['type' => $innerType])];

    $identifier = [
        'kind'  => 'GIST',
        'name'  => $className,
        'system_headers' => ['armadillo'],
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

  bool ShouldIterate() {
    bool done = (roundNum > 1) && convergingThisRound;
    return !done;
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
      /*std::cout << "Thread index: " << threadIndex << std::endl;
      std::cout << "Count: " << count << std::endl;
      std::cout << "Num threads: " << numThreads << std::endl;*/
      task.startIndex = threadIndex * count / numThreads;
      task.endIndex = (threadIndex + 1) * count / (numThreads - 1);
      //std::cout << "Got the start and end indices" << std::endl;
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
    /*std::cout << "Start: " << start << std::endl;
    std::cout << "End: " << end << std::endl;*/
    arma::Col<InnerType> medVal = 
      median(matrix.submat(start, 0, end, matrix.n_cols - 1), 1);
    arma::Col<InnerType> med(matrix.n_cols);
    med.fill(medVal(0, 0));
    matrix.submat(start, 0, end, matrix.n_cols - 1) - med.t();
  }

  void ColPolish(Task& task, cGLA& gla) {
    int start = task.startIndex;
    int end = task.endIndex;
    auto medVal = median(matrix.submat(0, start, matrix.n_rows - 1, end), 0);
    matrix.submat(start, 0, end, matrix.n_cols - 1) - medVal;
  }

 public:
  <?=$className?>(<?=const_typed_ref_args($states)?>) {
        matrix = <?=$matrix?>.GetMatrix();
        roundNum = 0;
      }

  // Advance the round number and distribute work among the threads
  void PrepareRound(WorkUnits& workers, int numThreads) {
    roundNum++;
    this->numThreads = numThreads;
    std::cout << "Beginning round " << roundNum << " with " << numThreads
      << " workers." << std::endl;
    std::pair<LocalScheduler*, cGLA*> worker;
    for (int counter = 0; counter < numThreads; counter++) {
      worker = std::make_pair(new LocalScheduler(counter, roundNum, numThreads,
        matrix), new cGLA(roundNum));
      workers.push_back(worker);
    }
    //std::cout << "Successfully prepared round." << std::endl;
  }

  // If round number is odd, do a row polish. Otherwise, do a column polish.
  void DoStep(Task& task, cGLA& gla) {
    if (roundNum % 2 == 1) {
      //std::cout << "Performing row polish." << std::endl;
      RowPolish(task, gla);
    } else {
      //std::cout << "Performing column polish." << std::endl;
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
