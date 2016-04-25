<?
// This functions converts between Armadillo objects. If no type is given, it is
// assumed that the output should simply toggle whether the input was a fixed
// size object. In this case, if the input is a variable sized matrix then the
// dimensions for the result must be given as nrow and ncol. Otherwise, the
// input is converted to the given output.
function Convert($inputs, $args) {
    // Processing of inputs.
    $count = count($inputs);
    grokit_assert($count == 1, "Convert expects exactly 1 input. $count given");
    $inputs_ = array_combine(['source'], $inputs);
    $input = $inputs_['source'];
    grokit_assert($input->is('armadillo'), "Convert: armadilo input expected. given $input");
    $inner = $input->get('type');

    // Processing of args.
    $output = get_first_key_default($args, ['type', 0], null);
    if ($output === null) {
        $fixed = $input->is('fixed');
        if ($fixed) {
            $output = lookupType('bio::variable_matrix', ['type' => $inner]);
        } else {
            $nrow = $t_args['nrow'];
            $ncol = $t_args['ncol'];
            $output = lookupType(
                'statistics::fixed_matrix',
                 ['nrow' => $nrow, 'ncol' => $ncol, 'type' => $inner]
            );
        }
    } else {
        grokit_assert(is_datatype($output), 'Convert: output should be a type');
    }

    // Whether armadillo must convert between inner types.
    $diff = $output->get('type') != $inner;
    if ($diff) {
       // If the inner types are different, we must convert to an intermediate
       // type first. Armadillo restricts this from being a fixed size.
       $args = $output->template_args();
       $args['type'] = $output->get('type');
       $intermediate = lookupType('bio::variable_matrix', ['type' => $output->get('type')]);
    }

    // The name of the function.
    $name = generate_name('Convert');

    $sys_headers     = ['armadillo'];
    $user_headers    = [];
    $lib_headers     = [];
    $libraries       = ['armadillo'];
?>

using namespace arma;

inline <?=$output?> <?=$name?>(<?=const_typed_ref_args($inputs_)?>) {
<?  if ($diff) { ?>
    auto intermediate = conv_to<<?=$intermediate?>>::from(source);
    return <?=$output?>(intermediate);
<?  } else { ?>
    return <?=$output?>(source);
<?  } ?>
}

<?
    return [
        'kind'           => 'FUNCTION',
        'name'           => $name,
        'input'          => $inputs,
        'result'         => $output,
        'deterministic'  => true,
        'system_headers' => $sys_headers,
        'user_headers'   => $user_headers,
        'lib_headers'    => $lib_headers,
        'libraries'      => $libraries,
    ];
}
?>