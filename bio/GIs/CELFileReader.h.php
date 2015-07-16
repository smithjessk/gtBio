<?
function CELFileReader(array $t_args, array $output) {
    // Class name for this GI.
    $className = generate_name('CELFileReader');

    // Locally named outputs.
    $output_ = array_combine(['intensity', 'stddev', 'pixels'], $output);

    $sys_headers  = ['string'];
    $user_headers = [];
    $lib_headers  = ['CELFileReader.h'];
    $libraries    = ['armadillo'];
?>

class <?=className?> {
 private:
  // Absolute file path of the CEL File to be read.
  std::string fileName;

  // Whether the single tuple for this file has been produced.
  bool finished;

 public:
  <?=className?>(GIStreamProxy& _stream)
      : fileName(_stream.get_file_name()),
        finished(false) {
  }

  bool ProduceTuple(<?=typed_ref_args($output_)?>) {
    if (!finished) {
      CELFileReader in(fileName.c_str());
      CELBase::pointer data = in.readFile();
      intensity = data->getIntensityMatrix();
      stddev = data->getStdDevMatrix();
      pixels = data->getPixelsMatrix();
      return finished = true;
    } else {
      return false;
    }
  }
};

<?
    return [
        'kind'           => 'GI',
        'name'           => $className,
        'system_headers' => $sys_headers,
        'user_headers'   => $user_headers,
        'lib_headers'    => $lib_headers,
        'libraries'      => $libraries,
        'output'         => $output
    ];
}
?>
