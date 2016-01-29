<?
function Build_Matrix_Constant_State(array $t_args) {
    $className = $t_args['className'];
    $states    = $t_args['states'];
    $numFiles = $t_args['numFiles'];

    $states_ = array_combine(['Count'], $states);

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
  arma::fmat entries;

 public:
  friend class <?=$className?>;

  <?=$className?>Constant_State(<?=const_typed_ref_args($states_)?>) {
    Count.GetResult(num_fids);
    entries.set_size(num_fids, <?=$numFiles?>);
    entries.fill(0);
  }
};
<?
  return $identifier;
}

function Build_Matrix(array $t_args, array $inputs, array $outputs, 
    array $states) {
    $className = generate_name('Build_Matrix');
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
        ['className' => $className, 'states' => $states, 
        'numFiles' => $num_files]
    ); ?>

class <?=$className?> {
 private:
  // The constant state used to hold matrix.
  <?=$constantState?>& constant_state;
  long num_fids_processed;
  std::unordered_map<std::string, int> file_names; // file_name to column

  // Update the entries matrix and also mark that we filled out this spot
  void set(int row_index, int col_index, float intensity) {
    constant_state.entries(row_index, col_index) = intensity;
  }

  void init_col_names() {
    int column = 0;
<?  foreach ($file_names as &$file_name) { ?>
      file_names["<?=$file_name?>"] = column;
      column++;
<?  } ?>
  }

  int get_col(std::string file_name) {
    return file_names.at(file_name);
  }

 public:
  <?=$className?>(const <?=$constantState?>& state)
    : constant_state(const_cast<<?=$constantState?> &>(state)), 
      num_fids_processed(0),
      file_names(<?=$num_files?>) {
      init_col_names();
  }

  // if the fid was already entered for one column, use that row in the 
  // appropriate column. Else append it as a new row.
  void AddItem(<?=const_typed_ref_args($inputs_)?>) {
    num_fids_processed++;
    int col_index = get_col(file_name.ToString());
    constant_state.entries(ordered_fid, col_index) = intensity;
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
    return constant_state.entries;
  }

  void GetResult(<?=typed_ref_args($outputs_)?>) const {
    matrix = constant_state.entries;
  }
};

<?
    $identifier['generated_state'] = $constantState;
    return $identifier;
}
?>
