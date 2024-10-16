@extends('layouts.cyber_source_payment')

@section('content')
<form id="payment_confirmation" action="{{ config('boa.testing_form_post_url') }}" method="post">
    <input hidden type="submit" id="submit" value="Confirm"/>
</form>

<script>
    // this should really hide the form and its data from the FrontEnd or PostMan
    document.addEventListener('DOMContentLoaded', function() {
        var data = {!! json_encode($boaData) !!}; // Retrieve the form data from Blade

        var form = document.getElementById('payment_confirmation');

        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = data[key];
                input.id = key; // Set the id attribute based on the key
                form.appendChild(input);
            }
        }

        // Automatically submit the form
        document.getElementById('submit').click();
    });
</script>
@endsection