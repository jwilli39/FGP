<?php
    // Description: Registration page for new families
    session_cache_expire(30);
    session_start();
    
    require_once('include/input-validation.php');

    $loggedIn = false;
    if (isset($_SESSION['change-password'])) {
        header('Location: changePassword.php');
        die();
    }
    if (isset($_SESSION['_id'])) {
        $loggedIn = true;
        $accessLevel = $_SESSION['access_level'];
        $userID = $_SESSION['_id'];
    }

    // Require admin privileges
    /*if ($accessLevel < 2)
    {
        header('Location: login.php');
        echo 'bad access level';
        die();
    }*/
    

    // if (isset($_SESSION['_id'])) {
    //     header('Location: index.php');
    // } else {
    //     $_SESSION['logged_in'] = 1;
    //     $_SESSION['access_level'] = 0;
    //     $_SESSION['venue'] = "";
    //     $_SESSION['type'] = "";
    //     $_SESSION['_id'] = "guest";
    //     header('Location: personEdit.php?id=new');
    // }
?>
<!DOCTYPE html>
<html>
<head>
    <?php require_once('universal.inc'); ?>
    <title>FGP | Become an FGP Family </title>
</head>
<body>
    <?php
        require_once('header.php');
        require_once('domain/Person.php');
        require_once('database/dbPersons.php');
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // make every submitted field SQL-safe except for password
            $ignoreList = array('password');
            $args = sanitize($_POST, $ignoreList);

            //echo '<pre>'; print_r($args); echo '</pre>'; exit;


            $services = isset($args['services']) ? $args['services'] : [];
            //$permission_to_confirm = isset($args['permission_to_confirm']) ? $args['permission_to_confirm'] : "";

            //required fields
            $required = array('econtact-name','cmethod','phone','email',
                'address', 'city', 'state', 'zip', 'first-name', 'last-name', 'birthdate',
                'diagnosis','diagnosis_date','hospital',
                'agreement'
            );

            //process required fields, do input validation on some fields
            // $services=$args['services'];
            
            
            $errors = false;
            if (!wereRequiredFieldsSubmitted($args, $required)) {
                $errors = true;
            }
            $first = $args['first-name'];
            $last = $args['last-name'];
            $dateOfBirth = validateDate($args['birthdate']);
            if (!$dateOfBirth) {
                $errors = true;
                echo 'bad dob';
            }
            $diagnosis_date=$args['diagnosis_date'];
            /*$diagnosis_date = validateDate($args['diagnosis_date']);
            if (!$diagnosis_date) {
                $errors = true;
                echo 'bad diagnosis date';
            }*/

            $hospital=$args['hospital'];

            $cmethod=$args['cmethod'];
            if(!$cmethod="call" and !$cmethod='text'){
                $errors=true;
                echo 'bad contact method';
            }

            $expected_treatment_end_date=$args['expected_treatment_end_date'];

            $address = $args['address'];
            $city = $args['city'];
            $state = $args['state'];
            if (!valueConstrainedTo($state, array('AK', 'AL', 'AR', 'AZ', 'CA', 'CO', 'CT', 'DC', 'DE', 'FL', 'GA',
                    'HI', 'IA', 'ID', 'IL', 'IN', 'KS', 'KY', 'LA', 'MA', 'MD', 'ME',
                    'MI', 'MN', 'MO', 'MS', 'MT', 'NC', 'ND', 'NE', 'NH', 'NJ', 'NM',
                    'NV', 'NY', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX',
                    'UT', 'VA', 'VT', 'WA', 'WI', 'WV', 'WY'))) {
                $errors = true;
            }
            $zipcode = $args['zip'];
            if (!validateZipcode($zipcode)) {
                $errors = true;
                echo 'bad zip';
            }
            $email = strtolower($args['email']);
            $email = validateEmail($email);
            if (!$email) {
                $errors = true;
                echo 'bad email';
            }
            $phone = validateAndFilterPhoneNumber($args['phone']);
            if (!$phone) {
                $errors = true;
                echo 'bad phone';
            }

            $econtactName = $args['econtact-name'];
            $agreement=$args['agreement'];
            
            $diagnosis=$args['diagnosis'];
            $permission_to_confirm = isset($args['permission_to_confirm']) ? $args['permission_to_confirm'] : "";
           
            //set services options all to 0 so the variables are initialized 
            $meals=0;
            $housecleaning=0;
            $lawncare=0;
            $profphotos=0;
            $gas=0;
            $grocery=0;
            $aaaInterest=0;
            $socialEvents=0;
            $houseProjects=0;
            
            //iterate over array to check if value was passed via checkbox
            foreach ($services as $service){
                //echo $service . "<br>";
                switch ($service){
                    case 'meals':
                        $meals=1;
                        break;
                    case 'housecleaning':
                        $housecleaning=1;  
                        break;
                    case 'lawncare':
                        $lawncare=1;
                        break;
                    case 'profphotos':
                        $profphotos=1;
                        break;
                    case 'gascards':
                        $gas=1;  
                        break;  
                    case 'grocerycards':
                        $grocery=1;  
                        break;
                    case 'aaa':
                        $aaaInterest=1;  
                        break;
                    case 'socialEvents':
                        $socialEvents=1;  
                        break;
                    case 'houseProjects':
                        $houseProjects=1;  
                        break;
                }
            }
            
            // May want to enforce password requirements at this step
            $password=$dateOfBirth;
            //$password = password_hash($args['password'], PASSWORD_BCRYPT);

            if ($errors) {
                echo '<p>Your form submission contained unexpected input.</p>';
                die();
            }
            //optional field parsing
            $optional=array('allergies','sibling_info','can_share_contact_info',
            'family_info','how_did_you_hear','address2');

            //if field was set, set variable to input, otherwise set variable to empty string
            $allergies = isset($args['allergies']) ? $args['allergies'] : "";
            $sibling_info = isset($args['sibling_info']) ? $args['sibling_info'] : "";

            if($args['can_share_contact_info']){
                $can_share_contact_info=$args['can_share_contact_info'];
            }
            else{
                $can_share_contact_info="";
            }

            if($args['family_info']){
                $family_info=$args['family_info'];
            }
            else{
                $family_info="";
            }
            
            if($args['how_did_you_hear']){
                $how_did_you_hear=$args['how_did_you_hear'];
            }else{
                $how_did_you_hear="";
            }

            $ccp_sponsor_fgp = isset($args['ccp_sponsor_fgp']) ? $args['ccp_sponsor_fgp'] : "";
            
            if($args['address2']){
                $address=$address.", ".$args['address2'];
            }


            //create statement for person object
            $newperson = new Person(
                $first,                  // 1
                $last,                   // 2
                'portland',              // 3
                $address,                // 4
                $city,                   // 5
                $state,                  // 6
                $zipcode,                // 7
                "",                      // 8 - profile_pic
                $phone,                  // 9
                'cell',                  // 10
                null,                    // 11 - phone2
                null,                    // 12 - phone2type
                $email,                  // 13
                $econtactName,           // 14
                null,                    // 15 - contact_num
                null,                    // 16 - relation
                null,                    // 17 - contact_time
                'Family',                // 18 - type
                'Pending',               // 19 - status
                $cmethod,                // 20 - cMethod
                '',                      // 21 - availability
                '',                      // 22 - schedule
                '',                      // 23 - hours
                $dateOfBirth,            // 24 - birthday
                null,                    // 25 - start_date
                null,                    // 26 - notes
                $password,               // 27 - password
                null,                    // 28 - sundaysStart
                null,                    // 29 - sundaysEnd
                null,                    // 30 - mondaysStart
                null,                    // 31 - mondaysEnd
                null,                    // 32 - tuesdaysStart
                null,                    // 33 - tuesdaysEnd
                null,                    // 34 - wednesdaysStart
                null,                    // 35 - wednesdaysEnd
                null,                    // 36 - thursdaysStart
                null,                    // 37 - thursdaysEnd
                null,                    // 38 - fridaysStart
                null,                    // 39 - fridaysEnd
                null,                    // 40 - saturdaysStart
                null,                    // 41 - saturdaysEnd
                0,                       // 42 - mustChangePassword
                null,                    // 43 - gender
                $diagnosis,              // 44
                $diagnosis_date,         // 45
                $hospital,               // 46
                $permission_to_confirm,  // 47
                $expected_treatment_end_date, // 48
                $allergies,              // 49
                $sibling_info,           // 50
                $can_share_contact_info, // 51
                substr($first, 0,1).$last, // 52 - username
                $meals,                  // 53
                $housecleaning,          // 54
                $lawncare,               // 55
                $profphotos,             // 56 - NOT $photography
                $gas,                    // 57
                $grocery,                // 58
                $aaaInterest,            // 59
                $socialEvents,           // 60
                $houseProjects,          // 61
                $how_did_you_hear,       // 62
                $family_info,            // 63
                null,                    // 64 - leadVolunteer
                null,                    // 65 - gift_card_delivery_method
                null,                    // 66 - location
                null,                    // 67 - remission_trans_date
                null,                    // 68 - remission_end_date
                null,                    // 69 - remembrance_date
                $ccp_sponsor_fgp         // 70 ccp sponsor
            );
            $result = add_person($newperson);
            if (!$result) {
                echo '<p>That e-mail address is already in use.</p>';
            } else {
                if ($loggedIn) {
                    echo '<script>document.location = "index.php?registerSuccess";</script>';
                } else {
                    echo '<script>document.location = "login.php?registerSuccess";</script>';
                }
            }
        } else {
            require_once('createAccountForm.php'); 
        }
    ?>
</body>
</html>
