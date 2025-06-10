<!-- resources/views/oidc/callback.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <title>Fayda Callback</title>
</head>
<body>
    <h1>User Info</h1>
    <p>Name: {{ $name }}</p>
    <p>Email: {{ $email }}</p>
    <p>Phone: {{ $phone }}</p>
    <p>Birthdate: {{ $birthdate }}</p>
    <p>Gender: {{ $gender }}</p>
    <p>Address: {{ $address }}</p>
    <img src="{{ $picture }}" alt="User Picture" />
</body>
</html>
