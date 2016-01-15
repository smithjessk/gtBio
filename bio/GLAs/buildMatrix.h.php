<?
function Build_Matrix_Constant_State(array $t_args) {
  // Initialization of local variables from template arguments.
  $className = $t_args['className'];
  $states    = $t_args['states'];

  $states_ = array_combine(['Count'], $states);

  // Values to be used in C++ code.
  $state = array_keys($states)[0];
  $class = $states[$state];

  // Return values.
  $sys_headers = ['armadillo'];
  $user_headers = [];
  $lib_headers = [];
  $libraries = ['armadillo'];

  $identifier = [
    'kind'           => 'RESOURCE',
    'name'           => $className . 'Constant_State',
    'system_headers' => $sys_headers,
    'user_headers'   => $user_headers,
    'lib_headers'    => $lib_headers,
    'libraries'      => $libraries,
    'configurable'   => false,
  ];
?>

class <?=$className?>Constant_State {
 private:
  long num_fids;
 public:
  friend class <?=$className?>;

  <?=$className?>Constant_State(<?=const_typed_ref_args($states_)?>) {
    Count.GetResult(num_fids);
  }
};
<?
  return $identifier;
}

function Build_Matrix(array $t_args, array $inputs, array $outputs, array $states) {
  $className = generate_name('BuildMatrix');
  $inputs_ = array_combine(['file_name', 'ordered_fid', 'fid', 'intensity'], 
    $inputs);
  $output_type = [lookupType('statistics::Variable_Matrix', 
    ['type' => lookupType('float')])];
  $outputs_ = array_combine(['matrix'], $output_type);
  $file_names = $t_args['files'];
  $num_files = count($file_names);
  $sys_headers  = ['armadillo', 'unordered_map'];
  $user_headers = [];
  $lib_headers  = [];
  $libraries    = ['armadillo'];
  $properties   = ['matrix', 'tuples'];
  $extra        = [];
  $identifier = [
    'kind'              => 'GLA',
    'name'              => $className,
    'system_headers'    => $sys_headers,
    'user_headers'      => $user_headers,
    'lib_headers'       => $lib_headers,
    'libraries'         => $libraries,
    'iterable'          => false,
    'input'             => $inputs,
    'output'            => $output_type,
    'result_type'       => 'single',
    'finalize_as_state' => true,
    'properties'        => $properties,
    'extra'             => $extra,
  ];
?>

class <?=$className?>;

<?  $constantState = lookupResource(
        "bio::Build_Matrix_Constant_State",
        ['className' => $className, 'states' => $states]
    ); ?>

class <?=$className?> {
 private:
  // The constant state used to hold matrix.
  const <?=$constantState?>& constant_state;
  long num_fids_processed;
  std::unordered_map<std::string, int> file_names; // file_name to column
  arma::fmat entries;
  std::vector<std::vector<bool>> filled;
  std::unordered_map<int, int> fids; // fid to row

  // Update the entries matrix and also mark that we filled out this spot
  void set(int row_index, int col_index, float intensity) {
    entries(row_index, col_index) = intensity;
    filled.at(row_index).at(col_index) = true;
  }

  void init_col_names() {
    int column = 0;
<?  foreach ($file_names as &$file_name) { ?>
      file_names["<?=$file_name?>"] = column;
      column++;
<?  } ?>
  }

  // For each fid we could end up processing, enter a vector that has one entry
  // for each file name. This matrix is the same size as entries.
  void init_filled() {
    std::vector<bool> new_row(<?=$num_files?>);
    for (size_t j = 0; j < <?=$num_files?>; j++) {
      new_row.at(j) = false;
    }
    filled.resize(constant_state.num_fids, new_row);
  }

 public:
  <?=$className?>(const <?=$constantState?>& state)
    : constant_state(state), 
      num_fids_processed(0),
      file_names(<?=$num_files?>),
      entries(constant_state.num_fids, <?=$num_files?>) {
      entries.fill(0);
      fids = std::unordered_map<int, int>(constant_state.num_fids);
      init_col_names();
      init_filled();
  }

  int get_col(std::string file_name) {
    try {
      return file_names.at(file_name);
    } catch (const std::out_of_range &oor) {
      return -1;
    }
  }

  std::unordered_map<int, int> &get_fids() {
    return fids;
  }

  arma::fmat &get_entries() {
    return entries;
  }

  std::vector<std::vector<bool>> &get_filled() {
    return filled;
  }

  // if the fid was already entered for one column, use that row in the 
  // appropriate column. Else append it as a new row.
  void AddItem(<?=const_typed_ref_args($inputs_)?>) {
    int col_index = get_col(file_name.ToString());
    num_fids_processed++;
    set(ordered_fid, col_index, intensity);
  }

  // For every entry that other has set, take that value and put it in our 
  // entries matrix.
  void AddState(<?=$className?> &other) {
    std::cout << "Adding state" << std::endl;
    entries += other.entries;
  }

  void FinalizeState() {
    std::cout << "<?=$className?> finished" << std::endl;
  }

  const arma::Mat<float>& GetMatrix() const {
    return entries;
  }

  void GetResult(<?=typed_ref_args($outputs_)?>) const {
    matrix = entries;
  }
};

<?
    $identifier['generated_state'] = $constantState;
    return $identifier;
}
?>
