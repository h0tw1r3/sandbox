<?php

if(@$_REQUEST['is_ajax'] == 'yes')
{
    $username = $_REQUEST['username'];
    $password = $_REQUEST['password'];

    if($username == 'to_define' && $password == 'to_define')
    {
        echo "success";
    }
    else
    {
        echo "fail";
    }
    exit;
}
?>
<html>
    <head>
        <script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>   
        <style>
.success { color: green; }
.error { color: red; }
        </style>
    </head>
    <body>
        <div id="content">
            <h1>Login Form</h1>
            <p>Valid username/password: to_define/to_define</p>
            <form id="form1" name="form1" action="testform.php" method="post">
                <p><label for="username">Username: </label>
                <input type="text" name="username" id="username" /></p>
                <p><label for="password">Password: </label>
                <input type="password" name="password" id="password" /></p>
                <p><input type="submit" id="login" name="login" /></p>
            </form>
            <div id="message"></div>
        </div>
    </body>
</html>
<script type="text/javascript">
$(document).ready(function() {
    $("#login").click(function() {

        var action = $("#form1").attr('action');
        var form_data = {
                username: $("#username").val(),
                password: $("#password").val(),
                is_ajax: 'yes'
            };

        $.ajax({
            type: "POST",
            url: action,
            data: form_data,
            success: function(response)
            {
                if(response == 'success') {
                    $("#form1").slideUp('slow', function() {
                        $("#message").html("<p class='success'>logged in!</p>");
                    });
                } else {
                    $( "#message").html("<p class='error'>Invalid username and/orpassword</p>");
                }
            }
        });
        return false;
    });
});
</script>
