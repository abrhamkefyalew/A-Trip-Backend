<html>
    <head>
        <title>Secure Acceptance - Payment Form - TeleBirr</title>
        {{-- <script type="text/javascript" src="{{ asset('files/jquery-1.7.min.js') }}"></script> --}}
    </head>

    <body>

        <?php

            function useAnchorOpen($completeUrl) {
                echo '<a id="hiddenAnchor" href="' . htmlspecialchars($completeUrl) . '" target="_blank" rel="external" style="display:none;"> PAY </a>';
                echo '<script>document.getElementById("hiddenAnchor").click();</script>';
            }

            useAnchorOpen($completeUrl);
        ?>



    </body>
</html>