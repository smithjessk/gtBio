<?
function Median_Polish($t_args, $outputs, $states) {
    $className = generate_name('MP');
    $output = ['polished_matrix' => lookupType('Variable_Matrix')];

    $identifier = [
        'kind'  => 'GIST',
        'name'  => $className,
        'system_headers' => ['armadillo'],
        'libraries'     => ['armadillo'],
        'iterable'      => true,
        'output'        => $output,
        'result_type'   => 'single',
    ];
}
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
  struct Task {
    long startIndex; // Which row or column this local scheduler starts at
    long endIndex; // Which row/col it ends at. Inclusive bound.
  };

  struct LocalScheduler {
    int threadIndex;
    bool finishedScheduling;

    LocalScheduler(int index) :
        threadIndex(index),
        scheduledTask(false) {}

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

  struct Iterator {
    // The fragment ID for this tree, corresponding to the tree index.
    long fragmentID;

    // The index for the current output of this fragment.
    long fragmentIndex;
  };

  // We don't know what type of matrix we will be passed, so it is best to be
  // type-agnostic.
  using Matrix = <?=$states[0]?>::Matrix;
  using cGLA = ConvergenceGLA;
  using WorkUnit = pair<LocalScheduler*, cGLA*>;
  using WorkUnits = vector<WorkUnit>;

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
  <?=$className?>(<?=const_typed_ref_args($states_)?>):
      roundNum(0) {
    cout << "Constructed GIST state" << endl;
  }

  // Advance the round number and distribute work among the threads
  void PrepareRound(WorkUnits& workers, int numThreads) {
    roundNum++;
    this->numThreads = numThreads;
    cout << "Beginning round " << roundNum << " with " << numThreads
      << " workers." << endl;
    for (int counter = 0; counter < numThreads; counter++) {
      workers.push_back(new LocalScheduler(counter), new cGLA(roundNum));
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

  int GetNumFragments() {
    return numThreads;
  }

  Iterator* Finalize(long fragment) {}

  bool GetNextResult(Iterator* it, <?=typed_ref_args($outputs_)?>) {}
}

<?
    return $identifier;
?>
