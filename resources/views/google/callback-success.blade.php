<!DOCTYPE html>
<html>

<head>
    <title>{{ __('google.authenticating') }}</title>
</head>

<body>
    <script>
        // Notify the parent window
        if (window.opener) {
            window.opener.postMessage('google-auth-success', '*');
            // Small delay to ensure message is sent
            setTimeout(function() {
                window.close();
            }, 100);
        } else {
            // Fallback if not a popup
            window.location.href = "{{ route('dashboard') }}";
        }
    </script>
</body>

</html>
