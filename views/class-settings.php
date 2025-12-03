<?php
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1>JEM Embed Settings</h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('jemembed_settings');
        do_settings_sections('jemembed');
        submit_button();
        ?>
    </form>

    <h2>Debug / Test API Connection</h2>
    <form method="post">
        <input type="text" name="jem_test_url" style="width: 80%;" placeholder="Enter JEM JSON URL for testing">
        <input type="submit" name="jem_test_api" class="button button-secondary" value="Run Test">
    </form>

    <?php
    if (isset($_POST['jem_test_api']) && !empty($_POST['jem_test_url'])) {
        echo '<div class="jem-debug-output"><h3>API Test Results</h3>';
        echo JEM_Events_Debugger::run_test($_POST['jem_test_url']);
        echo '</div>';
    }
    ?>

</div>
