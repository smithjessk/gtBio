/**
 * A variable size matrix from Armadillo.
 */

<?

function Variable_Matrix(array $t_args) {
    $type = get_default($t_args, 'type', lookupType("base::double"));
    lookupType($type); // TODO: Automatically look-up type.
    grokit_assert(is_datatype($type),
                  'Matrix: [type] argument must be a valid datatype.');
    grokit_assert($type->is('numeric'),
                  'Matrix: [type] argument must be a numeric datatype.');
    $className       = generate_name('VarMatrix');
    $sys_headers     = ['armadillo', 'algorithm'];
    $user_headers    = [];
    $lib_headers     = ['ArmaJson'];
    $constructors    = [];
    $methods         = [];
    $functions       = [];
    $binaryOperators = [];
    $unaryOperators  = [];
    $globalContent   = '';
    $complex         = "ColumnVarIterator<@type, 8, 8>";
    $properties      = ['matrix'];
    $extra           = ['type' => $type];
}

?>

typedef arma::Mat<<?=$type?>> <?=$className?>;

<? ob_start(); ?>

template<>
inline size_t Serialize(char* buffer, const @type& src) {
  <?=$type?>* asInnerType = reinterpret_cast<<?=$type?>*>(buffer);
  const <?=$type?> * colPtr = src.memptr();
  std::copy(colPtr, colPtr + @type::n_elem, asInnerType);
  return @type::n_elem * sizeof(<?=$type?>);
}