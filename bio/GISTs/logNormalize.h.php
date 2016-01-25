<?
function Log_Normalize($t_args, $outputs, $states) {
    $class_name = generate_name('Log_Normalize');
    $cgla_name = generate_name('ConvergenceGLA');
    $matrix = array_keys($states)[0];
    $matrix_type = array_values($states)[0];
    $inner_type = array_values($states)[0]->get('type');
    $output = array_combine(array_keys($outputs), [
      lookupType('statistics::Variable_Matrix', ['type' => $inner_type]
    )]);
    $identifier = [
        'kind'  => 'GIST',
        'name'  => $class_name,
        'system_headers' => ['armadillo', 'algorithm', 'vector'],
        'libraries'     => ['armadillo'],
        'iterable'      => true,
        'output'        => $output,
        'result_type'   => 'single',
        'properties'      => ['matrix'],
        'intermediates'   => false,
        'extras'          => $matrix_type->extras(),
    ];
?>

class <?=$cgla_name?> {
 public:
  bool converging_this_round;

  <?=$cgla_name?>(int num)
    : converging_this_round(true) {}

  void AddState(<?=$cgla_name?> other) {
    converging_this_round = true;
  }

  bool ShouldIterate() {
    return false;
  }
}

class <?=$class_name?> {
 public:
  using Inner = <?=$inner_type?>;
  using Matrix = arma::Mat<Inner>;
  using cGLA = <?=$cgla_name?>;

  struct Task 
    std::vector<long> row_indices; // The rows on which this task will act
  };

  struct LocalScheduler {
    int thread_index;
    bool finished_scheduling;
    int num_threads;
    std::vector<std::vector<long>> &row_indices;

    LocalScheduler(int index, int num_threads, std::vector<std::vector<long>> &row_indices) :
        thread_index(index),
        finished_scheduling(false),
        num_threads(num_threads),
        row_indices(row_indices) {}

    // Index inside row_indices on which this task should act.
    int get_compound_index() {
      return thread_index * count / num_threads;
    }

    bool GetNextTask(Task& task) {
      bool ret = !finished_scheduling;
      task.row_indices = row_indices.at(get_compound_index());
      finished_scheduling = true;
      return ret;
    }
  };

  using WorkUnit = std::pair<LocalScheduler*, cGLA*>;
  using WorkUnits = std::vector<WorkUnit>;

 private:
  int num_threads;
  Matrix input;
  Matrix output;
  std::vector<std::vector<long>> row_indices;

  // For each entry in the set of entries for this task, take the base 2 log 
  // of the value and put it in output. 
  void LogNormalize(task, gla) {
    for (long row : task.indices) {
      auto row = input.row(row);
      output.row(row) = log2(row);
    }
  }

 public:
  <?=$class_name?>(<?=const_typed_ref_args($states)?>) {
    matrix = <?=$matrix?>.GetMatrix();
  }

  void PrepareRound(WorkUnits &workers, int suggested_num_workers) {
    this->num_threads = suggested_num_workers;
    std::pair<LocalScheduler*, cGLA*> worker;
    for (int i = 0; i < this->num_threads; i++) {
      LocalScheduler *ls = new LocalScheduler(counter, this->num_threads, 
        matrix);
      cGLA *cg = new cGLA();
      worker = std::make_pair(ls, cg);
      workers.push_back(worker);
    }
  }

  void DoStep(Task &task, cGLA &gla) {
    LogNormalize(task, gla);
  }

  void GetResult(<?=typed_ref_args($output)?>) {
    <?=array_keys($outputs)[0]?> = matrix;
  }

  inline const Matrix &GetMatrix() const {
    return matrix;
  }
}

<?
    return $identifier;
}
?>
