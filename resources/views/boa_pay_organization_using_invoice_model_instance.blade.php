{{-- WORKS --}}
@extends('layouts.cyber_source_payment')
@section('content')
<form id="payment_confirmation" action="{{ config('boa.testing') ? config('boa.testing_form_post_url') : config('boa.form_post_url') }}" method="post"/>

    <?php
        // Decode the JSON string into an associative array
        $params = json_decode($invoice->boa_request_payload, true);
    ?>

    <?php
        foreach($params as $name => $value) {
            echo "<input type=\"hidden\" id=\"" . $name . "\" name=\"" . $name . "\" value=\"" . $value . "\"/>\n";
        }
    ?>

<input hidden type="submit" id="submit" value="Confirm"/>
</form>

<script>
    jQuery(function(){
       jQuery('#submit').click();
    });
</script>
    
@endsection







{{-- WORKS --}}
{{-- @extends('layouts.cyber_source_payment')

@section('content')
<form id="payment_confirmation" action="{{ config('boa.testing') ? config('boa.testing_form_post_url') : config('boa.form_post_url') }}" method="post">
    @php
        // Decode the JSON string into an associative array
        $params = json_decode($invoice->boa_request_payload, true);
    @endphp

    @if (is_array($params))
        @foreach($params as $name => $value)
            <input type="hidden" id="{{ $name }}" name="{{ $name }}" value="{{ $value }}"/>
        @endforeach
    @endif

    <input hidden type="submit" id="submit" value="Confirm"/>
</form>


<script>
    jQuery(function(){
       jQuery('#submit').click();
    });
</script>
@endsection --}}


     