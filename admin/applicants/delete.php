<?php
require_once ("../../include/initialize.php");

// Check if the ID is provided in the URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Assuming tbljobregistration is the table associated with the entity
    $jobRegistration = new JobRegistration();
    $jobRegistration->delete($id);

    message("Record deleted successfully!", "success");
    redirect("index.php");
} else {
    message("Invalid request. Please provide an ID.", "error");
    redirect("index.php");
}
?>