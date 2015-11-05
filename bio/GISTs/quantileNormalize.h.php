<?
function Quantile_Normalize($t_args, $outputs, $states) {
    $class_name = generate_name('Quantile_Normalize');
    $cgla_name = generate_name('ConvergenceGLA');
    $matrix = array_keys($states)[0];
    $field_to_access = get_default($t_args, 'field_to_access', '');
    if ($field_to_access != '') {
      $field_to_access = '.' + $field_to_access;
    }
    $matrix_type = array_values($states)[0];
    $inner_type = $matrix_type->get('type');
    $should_transpose = get_default($t_args, 'should_transpose', False);
    $output = array_combine(array_keys($outputs), [
      lookupType('statistics::Variable_Matrix', ['type' => $inner_type]
    )]);
    $identifier = [
        'kind'  => 'GIST',
        'name'  => $class_name,
        'user_headers'   => ['qNorm.h'],
        'system_headers' => ['armadillo', 'algorithm'],
        'libraries'     => ['armadillo'],
        'iterable'      => true,
        'output'        => $output,
        'result_type'   => 'single',
        'properties'      => ['matrix'],
        'extras'          => $matrix_type->extras(),
    ];
?>

class <?=$cgla_name?> {
 public:
  int round_num;

  <?=$cgla_name?>(int num) :
      round_num(num) {}

  void AddState(<?=$cgla_name?> other) {}
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

  struct Task {
    long start_index; // Which row or column this local scheduler starts at
    long end_index; // Which row or column it ends at. Inclusive bound.
  };

  struct LocalScheduler {
    int thread_index;
    bool finished_scheduling;
    int num_threads;
    int &round_num;
    Matrix &matrix;

    LocalScheduler(int index, int &round_num, int num_threads, 
      Matrix &matrix) :
        thread_index(index),
        finished_scheduling(false),
        num_threads(num_threads),
        round_num(round_num),
        matrix(matrix) {}

    bool GetNextTask(Task& task) {
      bool ret = !finished_scheduling;
      long count = (round_num == 1) ? matrix.n_cols : matrix.n_rows;
      task.start_index = thread_index * count / num_threads;
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
  arma::umat indices;

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
    indices = arma::umat(matrix.n_rows, matrix.n_cols);
  }

  void PrepareRound(WorkUnits& workers, int suggested_num_workers) {
    round_num++;
    if (round_num % 2 == 1) {
      arma::uword n_cols = matrix.n_cols;
      this->num_threads = std::min(n_cols, suggested_num_workers);
    } else {
      arma::uword n_rows = matrix.n_rows;
      this->num_threads = std::min(n_rows, suggested_num_workers);
    }
    std::printf("Beginning round %d with %d workers.\n", round_num, 
      this->num_threads);
    std::pair<LocalScheduler*, cGLA*> worker;
    for (int counter = 0; counter < this->num_threads; counter++) {
      worker = std::make_pair(new LocalScheduler(counter, round_num, 
        this->num_threads, matrix), new cGLA(round_num));
      workers.push_back(worker);
    }
  }

  // In the first round, sort each column. Store the updated indices in the
  // appropriate column of indices.
  // In the second round, update each row with the appropriate value.
  // In the third round, rearrange each column.
  void DoStep(Task& task, cGLA& gla) {
    for (size_t index = task.start_index; index <= task.end_index; index++) {
      if (round_num == 1) {
        reversible_column_sort(matrix, indices, index);
      } else if (round_num == 2) {
        matrix.row(index).fill(accu(matrix.row(index)) / matrix.n_cols);    
      } else if (round_num == 3) {
        rearrange_column(matrix, indices, index);
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
