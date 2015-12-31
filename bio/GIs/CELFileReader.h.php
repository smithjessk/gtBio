<?
function generateType($name, $type) {
  return lookupType($name, ['type' => $type])
}

function CELFileReader(array $t_args, array $outputs) {
    $className = generate_name('CELFileReader');

    $floatType = lookupType('float');
    $intType = lookupType('int');
    $stringType = lookupType('string');

    $outputs = array_combine(array_keys($outputs), [
     $intType,
     $stringType,
     $intType,
     $floatType
    ]);

    $output_names = [
      'chip_number',
      'chip_type',
      'fid',
      'intensity'
    ];

    // Locally named outputs. Used for ProduceTuple
    $outputs_ = array_combine($output_names, $outputs);

    $identifier = [
        'kind'           => 'GI',
        'name'           => $className,
        'system_headers' => ['string'],
        'user_headers'   => [],
        'lib_headers'    => ['CELFileReader.h'],
        'libraries'      => ['armadillo'],
        'output'         => $outputs
    ];
?>

class <?=$className?> {
 private:
  // Whether the single tuple for this file has been produced.
  bool finished;

  // Relative to this query
  int _chip_number;

  // Read from the CEL file
  arma::fmat _intensity_matrix;
  arma::fmat _std_dev_matrix;
  arma::fmat _pixels_matrix;
  std::string _chip_type;

  // Used to iterate through the chips' entries, row by row.
  int _fid;

  // file_name must be an absolute path
  void initialize_matrices(std::string file_ame) {
    gtBio::CELFileReader in(file_name.c_str());
    gtBio::CELBase::pointer data = in.readFile();
    intensity_matrix = data->getIntensityMatrix();
    std_dev_matrix = data->getStdDevMatrix();
    pixels_matrix = data->getPixelsMatrix();
    chip_type = data->get_chip_type();
  }

 public:
  <?=$className?>(GIStreamProxy& _stream)
    :  finished(false),
    _fid(0) {
   initialize_matrices_for_file(_stream.get_file_name());
  }

  bool ProduceTuple(<?=typed_ref_args($outputs_)?>) {
    if (!finished) {
      _fid++;
      int row_index = fid / intensity_matrix.n_col;
      int col_index = fid % intensity_matrix.n_col;
      chip_number = _chip_number;
      chip_type = _chip_type;
      fid = _fid;
      intensity = intensity_matrix(row_index, col_index);
      if (fid < intensity_matrix.n_elem) {
        return true;
      } else {
        finished = true;
        return true;
      }
    } else {
      return false;
    }
  }
};

<?
    return $identifier;
}
?>
