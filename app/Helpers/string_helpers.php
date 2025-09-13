<?php

if (!function_exists('snake_to_title')) { // this if condition is to check = IF a function with the name 'abort_if_inactive' is defined/created/exists elsewhere in the project, THEN do NOT redefine/recreate it here again 
                                                                            //  IF this helper file is loaded multiple times in a code, the function will be redefined again // so do NOT redeclare the function for multiple loads either
                                                                            //
                                                                            // 'if (!function_exists('abort_if_inactive'))' will help us avoid the following error = = = prevents a "cannot redeclare function" fatal error 

    /**
     * Converts a snake_case string to Title Case.
     *
     * Example:
     *   snake_to_title("user_name")
     *      OUTPUT
     *        => "User Name"
     *
     * @param string $value The snake_case input string.
     * @return string The converted Title Case string.
     */
    function snake_to_title(string $value): string 
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}




// Generate a URL-safe slug from a string (e.g., "Café Déjà Vu!" -> "cafe-deja-vu")
if (!function_exists('slugify_string')) {

    /**
     * Converts a string into a URL-friendly slug.
     *
     * Handles Unicode transliteration, removes special characters,
     * replaces spaces/symbols with hyphens, and converts to lowercase.
     *
     * Example:
     *   slugify_string("Café Déjà Vu! — Hello World 2025")
     *      OUTPUT
     *        => "cafe-deja-vu-hello-world-2025"
     *
     * @param string $text The input string to slugify.
     * @return string The slugified version of the input.
     */
    function slugify_string(string $text): string 
    {
        // Original input example:
        // $text = "Café Déjà Vu! — Hello World 2025"

        // Step 1: Replace all non-letter or non-digit characters with a hyphen
        // Regex: [^\pL\d]+ means "anything that is not a letter (\pL) or digit (\d)"
        // Example output: "Café-Déjà-Vu-Hello-World-2025"
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // Step 2: Transliterate characters to ASCII
        // Converts accented characters to basic ones (é -> e, ü -> u, etc.)
        // Example output: "Cafe-Deja-Vu-Hello-World-2025"
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);

        // Step 3: Remove any characters that are not hyphens, letters, or digits
        // Removes leftover punctuation or symbols after transliteration
        // Example output: "Cafe-Deja-Vu-Hello-World-2025" (no visible change if clean)
        $text = preg_replace('~[^-\w]+~', '', $text);

        // Step 4: Trim hyphens from the beginning and end
        // In case the string started/ended with special characters replaced by hyphens
        // Example: "-Hello-World-" becomes "Hello-World"
        // Example output: "Cafe-Deja-Vu-Hello-World-2025"
        $text = trim($text, '-');

        // Step 5: Replace multiple consecutive hyphens with a single one
        // Useful if multiple special characters were in a row
        // Example: "Hello---World" becomes "Hello-World"
        // Example output: "Cafe-Deja-Vu-Hello-World-2025"
        $text = preg_replace('~-+~', '-', $text);

        // Step 6: Convert everything to lowercase
        // For consistency and SEO-friendliness
        // Example output: "cafe-deja-vu-hello-world-2025"
        $text = strtolower($text);

        // Step 7: Return the final slug
        return $text;
    }

}
