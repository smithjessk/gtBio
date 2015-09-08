/**
 * A variable size matrix from Armadillo.
 */

<?

function Variable_Matrix(array $t_args) {
    $type = get_default($t_args, 'type', lookupType("base::double"));

    lookupType($type);
    grokit_assert(is_datatype($type),
                  'Matrix: [type] argument must be a valid datatype.');
    grokit_assert($type->is('numeric'),
                  'Matrix: [type] argument must be a numeric datatype.');

    $className = generate_name('VarMatrix');

    $functions = [];

    $methods = [];
    $methods[] = [ 'Mean', [], 'base::float', true ];

    $innerDesc = function($var, $myType) use($type) {
      $describer = $type->describer('json');
?>
      <?=$var?>["fixed"] = false;
<?
      $innerVar = "{$var}[\"inner_type\"]";
      $describer($innerVar, $type);
    };


    $identifier = [
        'kind'             => 'TYPE',
        'name'             => $className,
        'system_headers'   => ['armadillo', 'algorithm'],
        'user_headers'     => ['ColumnVarIterator.h'],
        'lib_headers'      => [],
        'constructors'     => [],
        'methods'          => $methods,
        'functions'        => $functions,
        'binary_operators' => [],
        'unary_operators'  => [],
        'global_content'   => '',
        'complex'          => "ColumnVarIterator<@type, 8, 8>",
        'properties'       => ['matrix'],
        'extra'            => ['type' => $type],
        'describe_json'    => DescribeJson('matrix', $innerDesc),
    ];

?>

typedef arma::Mat<<?=$type?>> <?=$className?>;

<? ob_start(); ?>

template<>
/**
 * Write a Variable_Matrix to disk
 * @param {[type]} char* buffer [description]
 * @param {[type]} const @type& src           [description]
 */
inline size_t Serialize(char* buffer, const @type& src) {
  // Write number of rows and columns
  uint32_t* asInts = reinterpret_cast<uint32_t*>(buffer);
  asInts[0] = src.n_rows;
  asInts[1] = src.n_cols;

  // Write data
  <?=$type?>* asInnerType = reinterpret_cast<<?=$type?>*>(asInts + 2);
  const <?=$type?> * colPtr = src.memptr();
  std::copy(colPtr, colPtr + src.n_elem, asInnerType);

  // Return bytes read
  return 8 + src.n_elem * sizeof(<?=$type?>);
}

template<>
inline size_t SerializedSize(const @type& src) {
  return 8 + src.n_elem * sizeof(<?=$type?>);
}

template<>
inline size_t Deserialize(const char* buffer, @type& src) {
  uint32_t nRows = ((uint32_t*) buffer)[0];
  uint32_t nCols = ((uint32_t*) buffer)[1];

  src.set_size(nRows, nCols);
  const <?=$type?>* asInnerType = reinterpret_cast<const <?=$type?>*>(buffer + 8);
  std::copy(asInnerType, asInnerType + (nRows * nCols), src.memptr());

  return 8 + (nRows * nCols * sizeof(<?=$type?>));
}

template<>
inline size_t SizeFromBuffer<@type>(const char* buffer) {
  uint32_t nRows = ((uint32_t*) buffer)[0];
  uint32_t nCols = ((uint32_t*) buffer)[1];
  return 8 + (nRows * nCols * sizeof(<?=$type?>));
}

inline void ToJson(const @type& src, Json::Value& dest) {
  dest["__type__"] = "matrix";
  dest["n_rows"] = src.n_rows;
  dest["n_cols"] = src.n_cols;
  Json::Value content(Json::arrayValue);
  for (int i = 0; i < src.n_rows; i++)
    for (int j = 0; j < src.n_cols; j++)
      content[i * src.n_cols + j] = src(i, j);
  dest["data"] = content;
}

<?  $identifier['global_content'] = ob_get_clean(); ?>

<?
    return $identifier;
}

declareType('VariableMatrix', 'bio::Variable_Matrix', []);

function MatrixMean($args, $targs) {
  grokit_assert(\count($args) == 1,
    'MatrixMean supports exactly 1 input, ' . \count($args) . ' given');

  $inputType = lookupType($args[0]);

  grokit_assert($inputType->is('matrix'),
    'MatrixMean given non-matrix input');

  $resultType = $inputType->get('type');
  $funcName = generate_name('MatrixMean');
?>

<?=$resultType?> <?=$funcName?>( const <?=$inputType?>& x ) {
  return mean(mean(x));
}

<?
  return [
      'kind' => 'FUNCTION',
      'input' => $args,
      'name' => $funcName,
      'args' => [ $inputType ],
      'result' => $resultType,
      'determinsitic' => true
  ];
}
?>