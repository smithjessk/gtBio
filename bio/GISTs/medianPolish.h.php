// Median polish could take the whole matrix and then assign the various 
// probesets to the threads. For simplicity, a round-robin distribution would
// work. 
// Within each worker, it should probably be fine to use one thread for all of
// the work. The effective datasize over which every worker will be operating 
// will be of size (number of chips) * (number of rows for this probset). So 
// realistically like maybe 5, 10 thousand?

<?
function Median_Polish($t_args, $outputs, $states) {
    $class_name = generate_name('Median_Polish');
    $cgla_name = generate_name('ConvergenceGLA');
    $matrix = array_keys($states)[0];
    $probeset_map = array_keys($states)[1];
    $field_to_access = get_default($t_args, 'field_to_access', '');
    if ($field_to_access != '') {
      $field_to_access = '.' + $field_to_access;
    }
    $matrix_type = array_values($states)[0];
    $inner_type = array_values($states)[0]->get('type');
    $should_transpose = get_default($t_args, 'should_transpose', False);
    $output = array_combine(array_keys($outputs), [
      lookupType('statistics::Variable_Matrix', ['type' => $inner_type]
    )]);
    $identifier = [
        'kind'  => 'GIST',
        'name'  => $class_name,
        'system_headers' => ['armadillo', 'algorithm'],
        'libraries'     => ['armadillo'],
        'iterable'      => true,
        'output'        => $output,
        'result_type'   => 'single',
        'properties'      => ['matrix'],
        'intermediates'   => false,
        'extras'          => $matrix_type->extras(),
    ];
?>


// Found in preprocessCore
extern "C" void MedianPolish(double *data, size_t rows, size_t cols, int *cur_rows, double *results, size_t nprobes, double *resultsSE)

class <?=$cgla_name?> {
 public:
  int round_num;
  bool converging_this_round;

  <?=$cgla_name?>(int num) :
      round_num(num),
      converging_this_round(true) {}

  void AddState(<?=$cgla_name?> other) {
    converging_this_round = converging_this_round && 
      other.converging_this_round;
  }

  bool ShouldIterate() {
    return false;
  }
};

class <?=$class_name?> {
 public:
  // We don't know what type of matrix we will be passed, so it is best to be
  // type-agnostic.
  using Inner = <?=$inner_type?>;
  using Matrix = arma::Mat<Inner>;
  using cGLA = <?=$cgla_name?>;
  using Map = std::unordered_map<int, arma::vec<int>>;

  struct Task {
    int fsetid;
  };

  struct LocalScheduler {
    int thread_index;
    bool finished_scheduling;
    int num_threads;
    int &round_num;
    Matrix &matrix;
    Map &probeset_map;
    int num_probesets;

    LocalScheduler(int index, int &round_num, int num_threads, 
      Matrix &matrix, Map &probeset_map, int num_probesets) :
        thread_index(index),
        finished_scheduling(false),
        num_threads(num_threads),
        round_num(round_num),
        matrix(matrix),
        probeset_map(probeset_map),
        num_probesets(num_probesets); {}

    bool GetNextTask(Task& task) {
      bool ret = !finished_scheduling;
      task.start_index = thread_index * num_probesets / num_threads;
      task.end_index = (thread_index + 1) * count / num_threads - 1;
      finished_scheduling = true;
      return ret;
    }
  };

  using WorkUnit = std::pair<LocalScheduler*, cGLA*>;
  using WorkUnits = std::vector<WorkUnit>;

 private:
  int round_num;
  int num_threads;
  Matrix matrix;
  Map probeset_map;

 public:
  <?=$class_name?>(<?=const_typed_ref_args($states)?>) {

<? if ($should_transpose) { ?>
        std::cout << "Transposing matrix..." << std::endl;
        <? if ($field_to_access != '') { ?> 
          matrix = <?=$matrix?>.GetMatrix().<?=$field_to_access?>.t();
        <? } else { ?>
          matrix = <?=$matrix?>.GetMatrix().t();
        <? } ?>
<? } else { ?>
        <? if ($field_to_access != '') { ?> 
          matrix = <?=$matrix?>.GetMatrix().<?=$field_to_access?>;
        <? } else { ?>
          matrix = <?=$matrix?>.GetMatrix();
        <? } ?>
<? } ?> 
    round_num = 0;
    probeset_map = <?=$probeset_map?>.GetResult();
  }

  void PrepareRound(WorkUnits& workers, int suggested_num_workers) {
    round_num++;
    this->num_threads = std::min(num_probesets, suggested_num_workers);
    for (int counter = 0; counter < this->num_threads; counter++) {
      LocalScheduler *ls = new LocalScheduler(counter, round_num, 
        this->num_threads, matrix, probeset_map);
      cGLA *cg = new cGLA(round_num);
      workers.push_back(std::make_pair(ls, cg));
    }
  }

  // If round number is odd, do a row polish. Otherwise, do a column polish.
  void DoStep(Task& task, cGLA& gla) {
    for (j = 0; j < num_probesets; j++) {
      cur_rows = INTEGER_POINTER(VECTOR_ELT(R_rowIndexList,j));
      double *cur_rows = &probeset_map.at(j)[0];
      double *buffer = (double *) malloc(matrix.n_col * sizeof(double));
      double *other_buffer = (double *) malloc(matrix.n_col * sizeof(double));
      int num_probes = probeset_map.at(j).size();
      MedianPolish(matrix.memptr(), matrix.n_row, matrix.n_col, cur_rows, buffer, num_probes, other_buffer);
      for (i = 0; i < matrix.n_col; i++) {
        results[i * num_probesets + j] = buffer[i];
      }
    }
  }

  void GetResult(<?=typed_ref_args($output)?>) {
    <? if ($should_transpose) { ?>
      <?=array_keys($outputs)[0]?> = matrix.t();
    <? } else { ?>
      <?=array_keys($outputs)[0]?> = matrix;
    <? } ?>
  }

  inline const Matrix& GetMatrix() const {
    <? if ($should_transpose) { ?>
      return matrix.t();
    <? } else { ?>
      return matrix;
    <? } ?>
  }
};

<?
    return $identifier;
}
?>
