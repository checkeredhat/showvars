<?php
/**
 * Plugin Name: Show Vars Debugger
 * Description: Displays a modal with environment variables when ?showvars=1 is in the URL. Restricted to admin users only.
 * Version: 1.1
 * Author: James Schweda
 */

// Hook the function into the WordPress footer so that the modal HTML is output at the end of the page
add_action('wp_footer', 'svd_render_debug_modal');

/**
 * Renders a modal on the front-end showing all defined variables
 * when `?showvars=1` is passed in the URL — but only for admin users.
 */
function svd_render_debug_modal() {
    // Exit early if the `showvars` GET parameter is not set to 1
    if (!isset($_GET['showvars']) || $_GET['showvars'] != '1') {
        return;
    }

    // Restrict access to administrators only
    if (!current_user_can('administrator')) {
        return;
    }

    // Create a new array to hold only the relevant superglobals for debugging.
    // This avoids the memory exhaustion and recursion issues of printing $GLOBALS directly.
    $debug_vars = [
        'GET' => $_GET,
        'POST' => $_POST,
        'SERVER' => $_SERVER,
        'FILES' => $_FILES,
        'COOKIE' => $_COOKIE,
        'SESSION' => isset($_SESSION) ? $_SESSION : 'Session Not Active',
        'ENV' => $_ENV,
    ];

    // --- Section to specifically extract Cloudflare headers ---
    $cloudflare_headers = [];
    // Iterate through all server variables to find headers starting with 'HTTP_CF_'.
    foreach ($_SERVER as $key => $value) {
        // Check if the key starts with the Cloudflare prefix.
        if (strpos($key, 'HTTP_CF_') === 0) {
            // Clean up the key for better readability in the output.
            // (e.g., from 'HTTP_CF_REGION_CODE' to 'CF_REGION_CODE').
            $clean_key = substr($key, 5); 
            $cloudflare_headers[$clean_key] = $value;
        }
    }

    // Add the collected Cloudflare headers as a separate, easy-to-find group
    // in our main debug array. This array will be empty if the site is not
    // accessed through the Cloudflare network.
    $debug_vars['CLOUDFLARE_HEADERS'] = $cloudflare_headers;
    // --- End of Cloudflare section ---


    // Capture the curated list of variables for output
    ob_start(); // Start output buffering
    echo "<pre>";
    // Print the safe, curated array of variables
    print_r($debug_vars);
    echo "</pre>";
    $output = ob_get_clean(); // Get the buffered output and clear the buffer

    // Escape output for safe inclusion inside HTML <textarea>
    $escaped_output = esc_textarea($output);
    
    // Escape output for safe inclusion inside HTML <textarea>
    $escaped_output = esc_textarea($output);

    ?>

    <!-- Modal HTML structure -->
    <div id="svd-debug-modal" style="display: none;">
        <!-- Dark background overlay -->
        <div id="svd-modal-overlay"></div>

        <!-- Content container: resizable and scrollable -->
        <div id="svd-modal-content" contenteditable="false">
            <!-- Close button in the top-right corner -->
            <button id="svd-close-btn">×</button>

            <!-- Textarea to hold the debug output -->
            <textarea readonly><?php echo $escaped_output; ?></textarea>
        </div>
    </div>

    <!-- Modal CSS styling -->
    <style>
        /* Full-screen modal container */
        #svd-debug-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 99999; /* Ensure it appears on top of all other content */
        }

        /* Semi-transparent background overlay */
        #svd-modal-overlay {
            position: absolute;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        /* Modal content window */
        #svd-modal-content {
            position: fixed;
            top: 5%;
            left: 5%;
            width: 90%;
            height: 90%;
            background: #fff;
            padding: 1rem;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.7);
            overflow: auto;
            resize: both; /* Allow resizing */
        }

        /* Output area using monospace for better readability */
        #svd-modal-content textarea {
            width: 100%;
            height: 90%;
            font-family: monospace;
            white-space: pre;
            overflow: scroll;
            background-color: #f7f7f7;
            border: 1px solid #ccc;
            padding: 1rem;
        }

        /* Close button styling */
        #svd-close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e00;
            color: white;
            border: none;
            padding: 0.5rem;
            font-size: 1.2rem;
            cursor: pointer;
        }
    </style>

    <!-- JavaScript to handle modal display and close interaction -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('svd-debug-modal');
            const closeBtn = document.getElementById('svd-close-btn');

            // Show modal once DOM is fully loaded
            modal.style.display = 'block';

            // Close the modal when the close button is clicked
            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        });
    </script>

    <?php
}

