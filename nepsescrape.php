<?php
/////////////////////////////////////TO SCRAPE DATA OFF THE NEPSE WEBSITE//////////////////////////////////////////////

//Establishing Connection with Database
$conn = mysqli_connect("localhost","root","","share");

//Check for any connection error
if (!$conn){
	die("Connection Unsuccessful!!".mysqli_connect_error());
}
//--------------------------------------------------------------------------------------------------------------------
	
function scrape_between($data, $start, $end){
    $data = stristr($data, $start); // Stripping all data from before $start
    $data = substr($data, strlen($start));  // Stripping $start
    $stop = stripos($data, $end);   // Getting the position of the $end of the data to scrape
    $data = substr($data, 0, $stop);    // Stripping all data from after and including the $end of the data to scrape
    return $data;   // Returning the scraped data from the function
}
	
function curl($url) {
    // Assigning cURL options to an array
    $options = Array(
        CURLOPT_RETURNTRANSFER => TRUE,  // Setting cURL's option to return the webpage data
        CURLOPT_FOLLOWLOCATION => TRUE,  // Setting cURL to follow 'location' HTTP headers
        CURLOPT_AUTOREFERER => TRUE, // Automatically set the referer where following 'location' HTTP headers
        CURLOPT_CONNECTTIMEOUT => 120,   // Setting the amount of time (in seconds) before the request times out
        CURLOPT_TIMEOUT => 120,  // Setting the maximum amount of time for cURL to execute queries
        CURLOPT_MAXREDIRS => 10, // Setting the maximum number of redirections to follow
        CURLOPT_USERAGENT => "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1a2pre) Gecko/2008073000 Shredder/3.0a2pre ThunderBrowse/3.2.1.8",  // Setting the user agent
        CURLOPT_URL => $url, // Setting cURL's URL option with the $url variable passed into the function
    );
         
    $ch = curl_init();  // Initialising cURL 
    curl_setopt_array($ch, $options);   // Setting cURL's options using the previously assigned array data in $options
    $data = curl_exec($ch); // Executing the cURL request and assigning the returned data to the $data variable
    curl_close($ch);    // Closing cURL 
    return $data;   // Returning the data from the function 
}

$url_content = curl("Nepalstock.com/todaysprice"); //Url of the page from which data is to be fetched
$url_content = scrape_between($url_content,"onmouseout='this.start();'>","</marquee>"); //To work only on the contents inside these tags.

/////////////////////////////////////TO REFINE THE SCRAPED DATA///////////////////////////////////////////////////////

$dom = new DOMDocument();
$dom->loadHTML($url_content); //Loading the scraped url content to the object
$contents = $dom->getElementsByTagName('b'); //To take all the contents inside the <b> tag

$diff = array();

foreach($contents as $content){ 
    $differences = $content->getElementsByTagName('span');
    foreach($differences as $difference){
        $diff[] = $difference->nodeValue;
    }
}

// To remove child nodes <span> and <img>
$xpath = new DOMXPath($dom);
$result = $xpath->query("//b/span");

$nodes_to_remove = array();

foreach($result as $node){
    $node->parentNode->removeChild($node);
}

$uncleanData = $dom->getElementsByTagName("b")->item(0)->textContent;

$symbol = preg_split("/[^A-Z]+/", $uncleanData, -1, PREG_SPLIT_NO_EMPTY);//To get the stock symbols using regex
$price = preg_split("/[^0-9]+[\s,]+/", $uncleanData, -1, PREG_SPLIT_NO_EMPTY);//To get closing price and traded shares using regex

/////////////////////////////////TO STORE THE REFINED DATA IN DATABASE////////////////////////////////////////////////

//Establishing Connection with Database
$conn = mysqli_connect("localhost","root","","share");

//Check for any connection error
if (!$conn){
	die("Connection Unsuccessful!!".mysqli_connect_error());
}


$col_name = "stock_symbol, closing_price, traded_share, difference"; //For Query
$j=0;
for($i=0; $i<sizeof($diff); $i++){
    $k = $j + 1;
    $query = "INSERT INTO data($col_name) VALUES ('$symbol[$i]', $price[$j], $price[$k], $diff[$i])";
	$insert = mysqli_query($conn, $query); //Performing the INSERT query
    $j += 2;
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Author : Apar Adhikari
//          github.com/apar-adhikari
//          aparadhikari@gmail.com
?>