<?
function Median_Polish($t_args, $outputs, $states) {
    $className = generate_name('MP');
    $output = ['polished_matrix' => lookupType('matrix')];

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
  int roundNumber;
  bool convergingThisRound;

  ConvergenceGLA(int num) :
      roundNumber(num),
      convergingThisRound(true) {}

  void AddState(ConvergenceGLA other) {
    convergingThisRound = convergingThisRound && other.convergingThisRound;
  }

  bool ShouldIterate() {
    bool done = (roundNumber > 1) && convergingThisRound;
    return !done;
  }
};

class <?=$className?> {
 public:
  struct Task {
    int index; // Which row or column this local scheduler starts at
    int width; // How many rows / cols to be assigned to this LS
  };

  struct SingleThreadScheduler {
    int threadIndex;
    bool scheduledTask;

    SingleThreadScheduler(int index) :
        threadIndex(index),
        scheduledTask(false) {}

    bool GetNextTask(Task& task) {
      bool ret = !finished;
      printf("Getting task from scheduler %d: %d\n", index, ret);
      task.index = index;
      finished = true;
      return ret;
    }
  };

  // Starts at 1. Without a hard limit on the number of iterations, median 
  // polishing could go on forever.
  int roundNumber;

  using cGLA = ConvergenceGLA;
  using WorkUnit = pair<LocalScheduler*, cGLA*>;
  using WorkUnits = vector<WorkUnit>;
}

<?
    return $identifier;
?>