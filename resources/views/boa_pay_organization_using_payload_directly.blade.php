@extends('layouts.cyber_source_payment')

@section('content')
<form id="payment_confirmation" action="{{ config('boa.testing') ? config('boa.testing_form_post_url') : config('boa.form_post_url') }}" method="post">
    @foreach($boaData as $name => $value)
        <input type="hidden" id="{{ $name }}" name="{{ $name }}" value="{{ $value }}"/>
    @endforeach
    <input hidden type="submit" id="submit" value="Confirm"/>
</form>


<script>
    jQuery(function(){
       jQuery('#submit').click();
    });
</script>
@endsection