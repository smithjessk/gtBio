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
  using Matrix = <?=$matrixType?>::Matrix;

  struct Task {
    long startIndex; // Which row or column this local scheduler starts at
    long endIndex; // Which row/col it ends at. Inclusive bound.
  };

  struct LocalScheduler {
    int threadIndex;
    bool finishedScheduling;
    int &roundNum;
    int &numThreads;
    Matrix &matrix;

    LocalScheduler(int index, int &roundNum, int &numThreads, Matrix &matrix) :
        threadIndex(index),
        finishedScheduling(false),
        roundNum(roundNum),
        numThreads(numThreads),
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
      task.endIndex = (threadIndex + 1) * count / (numThreads - 1);
      finishedScheduling = true;
      return ret;
    }
  };

  using cGLA = ConvergenceGLA;
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
    auto medians = median(matrix.submat(start, 0, end, matrix.n_cols - 1), 1);
    matrix.submat(start, 0, end, matrix.n_cols - 1) - medians;
  }

  void ColPolish(Task& task, cGLA& gla) {
    int start = task.startIndex;
    int end = task.endIndex;
    auto medians = median(matrix.submat(0, start, matrix.n_rows - 1, end), 0);
    matrix.submat(start, 0, end, matrix.n_cols - 1) - medians;
  }

 public:
  <?=$className?>(<?=const_typed_ref_args($states)?>) {
        matrix = <?=$matrix?>.GetMatrix();
        roundNum = 0;
        std::cout << "Constructed GIST state" << std::endl;
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
