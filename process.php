<?php
// Include the necessary initialization file
require_once("include/initialize.php");

// Get the 'action' parameter from the URL or set it to an empty string if not present
$action = (isset($_GET['action']) && $_GET['action'] != '') ? $_GET['action'] : '';

// Perform different actions based on the 'action' parameter
switch ($action) {
    case 'submitapplication':
        doSubmitApplication();
        break;

    case 'register':
        doRegister();
        break;

    case 'login':
        doLogin();
        break;
}

// Function to handle the submission of a job application
function doSubmitApplication()
{
    global $mydb;

    // Get the 'JOBID' from the URL
    $jobid = $_GET['JOBID'];

    // Generate unique IDs for the applicant and file
    $autonum = new Autonumber();
    $applicantid = $autonum->set_autonumber('APPLICANT');
    $autonum = new Autonumber();
    $fileid = $autonum->set_autonumber('FILEID');

    // Upload an image and get its location
    $picture = UploadImage();
    $location = "photos/" . $picture;

    if ($picture == "") {
        // Redirect if no picture was uploaded
        redirect(web_root . "index.php?q=apply&job=" . $jobid . "&view=personalinfo");
    } else {
        if (isset($_SESSION['APPLICANTID'])) {
            // Insert data into the database if the user is already logged in
            $sql = "INSERT INTO `tblattachmentfile` (FILEID,`USERATTACHMENTID`, `FILE_NAME`, `FILE_LOCATION`, `JOBID`) 
                    VALUES ('" . date('Y') . $fileid->AUTO . "','{$_SESSION['APPLICANTID']}','Resume','{$location}','{$jobid}')";
            $mydb->setQuery($sql);
            $res = $mydb->executeQuery();

            doUpdate($jobid, $fileid->AUTO);
        } else {
            // Insert data into the database for a new user
            $sql = "INSERT INTO `tblattachmentfile` (FILEID,`USERATTACHMENTID`, `FILE_NAME`, `FILE_LOCATION`, `JOBID`) 
                    VALUES ('" . date('Y') . $fileid->AUTO . "','" . date('Y') . $applicantid->AUTO . "','Resume','{$location}','{$jobid}')";
            $mydb->setQuery($sql);
            $res = $mydb->executeQuery();

            doInsert($jobid, $fileid->AUTO);

            $autonum = new Autonumber();
            $autonum->auto_update('APPLICANT');
        }
    }

    $autonum = new Autonumber();
    $autonum->auto_update('FILEID');
}

// Function to handle the insertion of applicant data
function doInsert($jobid = 0, $fileid = 0)
{
    if (isset($_POST['submit'])) {
        global $mydb;

        // Calculate the age based on the birthdate
        $birthdate = $_POST['year'] . '-' . $_POST['month'] . '-' . $_POST['day'];
        $age = date_diff(date_create($birthdate), date_create('today'))->y;

        if ($age < 20) {
            message("Invalid age. 20 years old and above is allowed.", "error");
            redirect("index.php?q=apply&view=personalinfo&job=" . $jobid);
        } else {
            $autonum = new Autonumber();
            $auto = $autonum->set_autonumber('APPLICANT');

            $applicant = new Applicants();
            $applicant->APPLICANTID = date('Y') . $auto->AUTO;
            $applicant->FNAME = $_POST['FNAME'];
            $applicant->LNAME = $_POST['LNAME'];
            $applicant->MNAME = $_POST['MNAME'];
            $applicant->ADDRESS = $_POST['ADDRESS'];
            $applicant->SEX = $_POST['optionsRadios'];
            $applicant->CIVILSTATUS = $_POST['CIVILSTATUS'];
            $applicant->BIRTHDATE = $birthdate;
            $applicant->BIRTHPLACE = $_POST['BIRTHPLACE'];
            $applicant->AGE = $age;
            $applicant->USERNAME = $_POST['USERNAME'];
            $applicant->PASS = sha1($_POST['PASS']);
            $applicant->EMAILADDRESS = $_POST['EMAILADDRESS'];
            $applicant->CONTACTNO = $_POST['TELNO'];
            $applicant->DEGREE = $_POST['DEGREE'];
            $applicant->create();

            // Retrieve company and job information
            $sql = "SELECT * FROM `tblcompany` c,`tbljob` j WHERE c.`COMPANYID`=j.`COMPANYID` AND JOBID = '{$jobid}'";
            $mydb->setQuery($sql);
            $result = $mydb->loadSingleResult();

            $jobreg = new JobRegistration();
            $jobreg->COMPANYID = $result->COMPANYID;
            $jobreg->JOBID = $result->JOBID;
            $jobreg->APPLICANTID = date('Y') . $auto->AUTO;
            $jobreg->APPLICANT = $_POST['FNAME'] . ' ' . $_POST['LNAME'];
            $jobreg->REGISTRATIONDATE = date('Y-m-d');
            $jobreg->FILEID = date('Y') . $fileid;
            $jobreg->REMARKS = 'Pending';
            $jobreg->DATETIMEAPPROVED = date('Y-m-d H:i');
            $jobreg->create();

            message("Your application already submitted. Please wait for the company confirmation if you are qualified for this job.", "success");
            redirect("index.php?q=success&job=" . $result->JOBID);
        }
    }
}

// Function to handle the update of applicant data
function doUpdate($jobid = 0, $fileid = 0)
{
    if (isset($_POST['submit'])) {
        global $mydb;

        $applicant = new Applicants();
        $appl = $applicant->single_applicant($_SESSION['APPLICANTID']);

        // Retrieve company and job information
        $sql = "SELECT * FROM `tblcompany` c,`tbljob` j WHERE c.`COMPANYID`=j.`COMPANYID` AND JOBID = '{$jobid}'";
        $mydb->setQuery($sql);
        $result = $mydb->loadSingleResult();

        $jobreg = new JobRegistration();
        $jobreg->COMPANYID = $result->COMPANYID;
        $jobreg->JOBID = $result->JOBID;
        $jobreg->APPLICANTID = $appl->APPLICANTID;
        $jobreg->APPLICANT = $appl->FNAME . ' ' . $appl->LNAME;
        $jobreg->REGISTRATIONDATE = date('Y-m-d');
        $jobreg->FILEID = date('Y') . $fileid;
        $jobreg->REMARKS = 'Pending';
        $jobreg->DATETIMEAPPROVED = date('Y-m-d H:i');
        $jobreg->create();

        message("Your application already submitted. Please wait for the company confirmation if you are qualified for this job.", "success");
        redirect("index.php?q=success&job=" . $result->JOBID);
    }
}

// Function to handle user registration
function doRegister()
{
    global $mydb;
    if (isset($_POST['btnRegister'])) {
        // Calculate the age based on the birthdate
        $birthdate = $_POST['year'] . '-' . $_POST['month'] . '-' . $_POST['day'];
        $age = date_diff(date_create($birthdate), date_create('today'))->y;

        if ($age < 20) {
            message("Invalid age. 20 years old and above is allowed.", "error");
            redirect("index.php?q=register");
        } else {
            $autonum = new Autonumber();
            $auto = $autonum->set_autonumber('APPLICANT');

			// this error undefined is for readable code not actually an error
            $applicant = new Applicants();
            $applicant->APPLICANTID = date('Y') . $auto->AUTO;
            $applicant->FNAME = $_POST['FNAME'];
            $applicant->LNAME = $_POST['LNAME'];
            $applicant->MNAME = $_POST['MNAME'];
            $applicant->ADDRESS = $_POST['ADDRESS'];
            $applicant->SEX = $_POST['optionsRadios'];
            $applicant->CIVILSTATUS = $_POST['CIVILSTATUS'];
            $applicant->BIRTHDATE = $birthdate;
            $applicant->BIRTHPLACE = $_POST['BIRTHPLACE'];
            $applicant->AGE = $age;
            $applicant->USERNAME = $_POST['USERNAME'];
            $applicant->PASS = sha1($_POST['PASS']);
            $applicant->EMAILADDRESS = $_POST['EMAILADDRESS'];
            $applicant->CONTACTNO = $_POST['TELNO'];
            $applicant->DEGREE = $_POST['DEGREE'];
            $applicant->create();

            $autonum = new Autonumber();
            $autonum->auto_update('APPLICANT');

            message("You are successfully registered to the site. You can login now!", "success");
            redirect("index.php?q=success");
        }
    }
}

// Function to handle user login
function doLogin()
{
    $email = trim($_POST['USERNAME']);
    $upass = trim($_POST['PASS']);
    $h_upass = sha1($upass);

    // Create an object of the Applicants class
    $applicant = new Applicants();

    // Attempt to authenticate the user
    $res = $applicant->applicantAuthentication($email, $h_upass);

    if ($res == true) {
        message("You are now successfully login!", "success");
        redirect(web_root . "applicant/");
    } else {
        echo "Account does not exist! Please contact Administrator.";
    }
}

// Function to upload an image
function UploadImage($jobid = 0)
{
    $target_dir = "applicant/photos/";
    $target_file = $target_dir . date("dmYhis") . basename($_FILES["picture"]["name"]);
    $uploadOk = 1;
    $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);

    if ($imageFileType != "jpg" || $imageFileType != "png" || $imageFileType != "jpeg"
        || $imageFileType != "gif") {
        if (move_uploaded_file($_FILES["picture"]["tmp_name"], $target_file)) {
            return date("dmYhis") . basename($_FILES["picture"]["name"]);
        } else {
            message("Error Uploading File", "error");
        }
    } else {
        message("File Not Supported", "error");
    }
}
?>