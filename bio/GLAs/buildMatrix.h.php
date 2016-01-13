<?
function BuildMatrix(array $t_args, array $inputs, array $outputs)
{
  $className = generate_name('BuildMatrix');
  $inputs_ = array_combine(['tuple'], $inputs);
  $tuple  = $inputs_['tuple'];
  $file_names = $t_args['files'];
  $num_files = count($file_names);
  $sys_headers  = ['armadillo', 'map'];
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
    'output'            => $outputs,
    'result_type'       => 'single',
    'finalize_as_state' => true,
    'properties'        => $properties,
    'extra'             => $extra,
  ];
?>

class <?=$className?>;

class <?=$className?> {
 public:
  using Inner = <?=$type?>;

 private:
  arma::fmat entries;
  std::vector<int> fids; // Index = the row in entries that holds the fid
  std::vector<std::string> file_names;
  
  void init_col_names() {
<?  foreach ($file_names as $file_name) { ?>
      file_names.push_back(<?=$file_name?>);
  }

  int get_row(int fid) {
    for (size_t i = 0; i < fids.size(); i++) {
      if (fids.get(i) == fid) {
        return i;
      }
    }
    return -1;
  }

  int get_col(std::string file_name) {
    for (size_t i = 0; i < file_names.size(); i++) {
      if (file_names.at(i) == file_name) {
        return i;
      }
    }
    return -1;
  }

 public:
  <?=$className?>()
    : entries(0, <?=$num_files?>),
      fids(0),
      file_names(0) {
        init_col_names();
    }

  // if the fid was already entered for one column, use that row in the 
  // appropriate column. Else append it as a new row to whichever column. May
  // need to do a resize.
  // (fid, fsetid, file_name, intensity)
  void AddItem(<?const_typed_ref_args($inputs)?>) {
    int row_index = get_row(fid),
      col_index = get_col(file_name);
    if (row_index == -1) {
      entries.resize(fid_to_row.size() + 1, <?=$num_files?>);
      fids.push_back(fid);
      row_index = get_row(fid);
    }
    entries(row_index, col_index) = intensity;
  }

  // For each fid in each column in other:
  // If the fid has already been entered into one of these columns, then find 
  // the appropriate row and column combination and put this value there.
  // Else, append the entry as a new row in the appropriate column. May need 
  // to do a resize.
  void AddState(<?=$className?> &other) {
    
  }

  void FinalizeState() {
    // The remaining whitespace is stripped.
    items.resize(kHeight, count);
    cout << "<?=$className?> processed " << count << " tuples" << endl;
  }

  const Mat<Inner>& GetMatrix() const {
    return items;
  }

  const vector<Tuple>& GetTuples() const {
    return extra;
  }

  long GetCount() const {
    return count;
  }
}

<?
    return $identifier;
}
?>
