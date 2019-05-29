<?php


/** ----------------------  = Helper functions = ---------------------- */

function clean($string){
    return htmlentities($string);
}


function redirect($location){
    return header("Location: {$location}");
}


function set_message($message){
    if(!empty($message)){
        $_SESSION['message'] = $message;
    }else{
        $message = "";
    }
}


function display_message(){
    if(isset($_SESSION['message'])){
        echo $_SESSION['message'];
        unset($_SESSION['message']);
    }
}


function token_generator(){
    $token = $_SESSION['token'] = md5(uniqid(mt_rand(), true));
    return $token;
}



function validation_errors($error_message){

$error_message=<<<DELIMITER

                <div class="alert alert-danger" role="alert">
                $error_message
                </div>

DELIMITER;

return $error_message;
}




function email_exists($email){

    $sql = "SELECT id from users WHERE email = '$email'";
    $result = query($sql);
    if(row_count($result) == 1){

        return true;
    }else{

        return false;

    }
}



function username_exists($username){

    $sql = "SELECT id from users WHERE username = '$username'";
    $result = query($sql);
    if(row_count($result) == 1){

        return true;
    }else{

        return false;

    }
}




function send_email($email,$subject,$msg, $headers){

    if(mail($email,$subject,$msg,$headers)){

    }
}


/** ----------------------  = End Helper functions = ---------------------- */


/** ----------------------  = Validation functions = ---------------------- */

function validate_user_registration(){

    $errors = [];
    $min = 3;
    $max = 20;
    
    if($_SERVER['REQUEST_METHOD'] == "POST"){


        $first_name = clean($_POST['first_name']);
        $last_name = clean($_POST['last_name']);
        $username = clean($_POST['username']);
        $email = clean($_POST['email']);
        $password = clean($_POST['password']);
        $confirm_password = clean($_POST['confirm_password']);


        if(strlen($first_name)< $min){
            $errors[] = "First name should not be less than {$min} characters";
        }

        if(strlen($last_name)< $min){
            $errors[] = "Last name should not be less than {$min} characters";
        }


        if(strlen($username)< $min){
            $errors[] = "username should not be less than {$min} characters";
        }

        if(username_exists($username)){
            $errors[] = "username already exists!";
        }


        if(strlen($first_name)> $max){
            $errors[] = "First name should not be more than {$max} characters";
        }

        if(strlen($last_name)> $max){
            $errors[] = "Last name should not be more than {$max} characters";
        }

        if(strlen($username)> $max){
            $errors[] = "username should not be more than {$max} characters";
        }

        if(strlen($email)> $max){
            $errors[] = "Email should not be more than {$max} characters";
        }


        if(email_exists($email)){
            $errors[] = "Email already exists!";
        }

        if($password !== $confirm_password){
            $errors[] = "Password fields do not match!";
        }
        




        if(!empty($errors)){

            foreach($errors as $error){

                echo validation_errors($error);


            }
        }else{
            if(register_user($first_name, $last_name, $username, $email, $password)){
              
                set_message("<div class='alert alert-success' role='alert'>Check your email or in spam folder for activation link</div>");
                redirect("index.php");
            }else{
                             
                set_message("<div class='alert alert-danger' role='alert'>Failed to register! Try again</div>");
                redirect("index.php"); 
            }

        }



    }
}





/** ----------------------  = End Validation functions = ---------------------- */


/** ----------------------  = Start Registration functions = ---------------------- */



function register_user($first_name, $last_name, $username, $email, $password){

    $first_name = escape($first_name);
    $last_name = escape($last_name);
    $username = escape($username);
    $email = escape($email);
    $password = escape($password);


    if(email_exists($email)){

        return false;
    }else if(username_exists($username)){
        
        return false;
    }else{

        $password = md5($password);
        $validation = md5($username);

        $sql = "INSERT INTO `users` (`first_name`, `last_name`, `username`, `email`, `password`, `validation_code`, `active`)";
        $sql .= " VALUES ('$first_name', '$last_name', '$username', '$email', '$password', '$validation', 0);";
        $result = query($sql);
        confirm($result);

        $subject = "Activate Account";
        $msg = "    Please click the link below to activate your account
        http://www.greentechinnovation.tech/activate.php?email=$email&code=$validation
        ";
        $headers = "From: greentechinnovator@group.com";

        send_email($email,$subject,$msg,$headers);
        return true;
    }


}








/** ----------------------  = End Registration functions = ---------------------- */





/** ----------------------  = Start Activation functions = ---------------------- */


function activate_user(){
    if($_SERVER['REQUEST_METHOD'] == 'GET'){
        if(isset($_GET['email'])){
            $email = clean($_GET['email']);
            $validation = clean($_GET['code']);


            $sql = "SELECT id from users WHERE email = '".escape($_GET['email'])."'AND validation_code = '".escape($_GET['code'])."'";
            $result = query($sql);
            confirm($result);

            if(row_count($result)== 1){

                $sql1 = "UPDATE users SET active = 1, validation_code = 0 WHERE email = '".escape($email)."' AND validation_code = '".escape($validation)."'";
                $result1 = query($sql1);
                confirm($result1);
                
                set_message("<p class='bg-success   '>Your fucking acc is activated!</p>");
                redirect("login.php");
            }else{
               
                echo "<p class='bg-danger'>Your fucking acc is not activated!</p>";
            }

        }
    }
}



/** ----------------------  = End Activation functions = ---------------------- */

/** ----------------------  = Start Validate Login functions = ---------------------- */



function validate_user_login(){

    $errors = [];
    $min = 3;
    $max = 20;
    
    if($_SERVER['REQUEST_METHOD'] == "POST"){

        $email = clean($_POST['email']);
        $password = clean($_POST['password']);



        if(empty($email)){
            $errors[] = "Email can't be blanked!";

        }

        if(empty($password)){
            $errors[] = "Password can't be emptied";

        }



        if(!empty($errors)){

            foreach($errors as $error){

                echo validation_errors($error);


            }
        }else{

           if(login_user($email, $password)){

            redirect("admin.php");
           }else{


            echo validation_errors("You got fucking wrong creditionals");
           }

        }


    }

}





/** ----------------------  = End Validate login functions = ---------------------- */

/** ----------------------  = Start login function = ---------------------- */

function login_user($email, $password){

    $sql = "SELECT password,id FROM users WHERE email = '".escape($email)."' AND active = 1";
    $result = query($sql);

    if(row_count($result) == 1){

        $row = fetch_array($result);
        $db_password = $row['password'];

        if(md5($password) == $db_password ){

            $_SESSION['email'] = $email;

            return true;
        }else{

            return false;
        }

        return true;

    }else{

        return false;

    }

}

/** ----------------------  = End login function = ---------------------- */



/** ----------------------  = Start logged session function = ---------------------- */


function logged_in(){
    if(isset($_SESSION['email'])){

        
        return true;

    }else{
        return false;
    }
}


/** ----------------------  = End logged session function = ---------------------- */