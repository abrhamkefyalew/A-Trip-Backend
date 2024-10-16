@extends('layouts.cyber_source_payment')

@section('content')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Create a hidden form element
        var form = document.createElement('form');
        form.style.display = 'none'; // Hide the form

        // Set form attributes
        form.method = 'post';
        form.action = '{{ config('boa.testing_form_post_url') }}';

        // Function to add input fields to the form
        function addInput(name, value) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }

        // Add input fields
        @foreach($boaData as $name => $value)
            addInput('{{$name}}', '{{$value}}');
        @endforeach

        // Append the form to the document body and submit it
        document.body.appendChild(form);
        form.submit();
    });
</script>
@endsection