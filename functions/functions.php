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
    
    if($_SERVER['REQUEST_METHOD'] == "POST"){

        $email = clean($_POST['email']);
        $password = clean($_POST['password']);
        $remember = isset($_POST['remember']);



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

           if(login_user($email, $password, $remember)){

            redirect("admin.php");
           }else{


            echo validation_errors("You got fucking wrong creditionals");
           }

        }


    }

}





/** ----------------------  = End Validate login functions = ---------------------- */

/** ----------------------  = Start login function = ---------------------- */

function login_user($email, $password, $remember){

    $sql = "SELECT password,id FROM users WHERE email = '".escape($email)."' AND active = 1";
    $result = query($sql);

    if(row_count($result) == 1){

        $row = fetch_array($result);
        $db_password = $row['password'];

        if(md5($password) == $db_password ){

            if($remember == "on"){

                setcookie('email', $email, time()+86400);
            }


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
    if(isset($_SESSION['email']) || isset($_COOKIE['email'])){

        
        return true;

    }else{
        return false;
    }
}


/** ----------------------  = End logged session function = ---------------------- */






/** ----------------------  = Start Password Recovery function = ---------------------- */



function recover_password(){

    if($_SERVER['REQUEST_METHOD'] == "POST"){


        if(isset($_SESSION['token']) && $_POST['token'] === $_SESSION['token']){

            $email = escape($_POST['email']);

            if(email_exists($email)){

                $validation_code = md5($email);
                setcookie('temp_access_code', $validation_code, time()+60);



                $sql = "UPDATE users SET validation_code ='".escape($validation_code)."' WHERE email = '".escape($email)."'";
                $result = query($sql);
               


                $subject = "Please reset your password";
                $message = "Here is your account reset code 
                {$validation_code}
                Click here to reset your password http://localhost/code.php?email=$email&code$validation_code
                ";
                $headers = "From: noreply@greentech.com";
                

                if(!send_email($email, $subject, $message, $headers)){

                    echo validation_errors("could not send the provided email!");

                }

                set_message("<p class='bg-success text-center'>Check your inbox or in spam folder for reset code!</p>");
                redirect("index.php");



            }
           
        }else{
            redirect("index.php");
        }


       
    }
}



/** ----------------------  = End Password Recovery function = ---------------------- */



/** ----------------------  = Start Reset Code Validation function = ---------------------- */



function validation_code(){


    if(isset($_COOKIE['temp_access_code'])){

      

            if(!isset($_GET['email']) && !isset($_GET['code'])){


                    redirect("index.php");


            }else if(empty($_GET['email']) || empty($_GET['code'])){

                    redirect("index.php");

            }else{

                    if(isset($_POST['code'])){
                        $email = clean($_GET['email']);
                        $validation_code = clean($_POST['code']);
                        $sql = "SELECT id FROM users WHERE validation_code = '".escape($validation_code)."' AND email = '".escape($email)."'";

                        $result = query($sql);
                       
                        if(row_count($result) == 1){

                            redirect("reset.php");

                        }else{

                            echo validation_errors("Wrong credentials!");
                            
                        }



                    }

            }



    }else{
        set_message("<p class='bg-danger text-center'>Sorry failed to perform!</p>");
        redirect("recover.php");
    }


}


/** ----------------------  = End Reset Code Validation function = ---------------------- */
