<?php
// Database configuration
$servername = "localhost";   
$username = "root";         
$password = "";             
$dbname = "yashdb";          

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

echo "Connected successfully!";

//create

$sql="Insert Into user(name,email)VALUES('yash','yash@examplemail.com')";
$sql="Insert Into user(name,email)VALUES('sam','sam@examplemail.com')";


if($conn->query($sql)===TRUE){
	echo "<br>new record created or inserted";
}
else{
	echo "ERROR" .$sql. "<br>" . $conn->error;
}

//read

$sql = "SELECT * FROM user WHERE id = 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row["id"] . " - Name: " . $row["name"] . " - Email: " . $row["email"] . "<br>";
    }
} else {
    echo "No result";
}

// UPDATE

$new_email = "abc@example1.com";
$sql = "UPDATE user SET email='$new_email'";

if ($conn->query($sql) === TRUE) {
    echo "<br>Record updated successfully!";
} else {
    echo "Error updating record: " . $conn->error;
}

//delete

$sql="DELETE FROM user WHERE email='abc@example1.com'";

if ($conn->query ($sql)===TRUE){
	echo "<br>record deleted";
	}
else{
	echo "error : ".$conn->error;
	}


$conn->close();
?>