<?php
ini_set("display_errors", 1);
create_db_connection();
@ini_set('zlib.output_compression',0);
@ini_set('implicit_flush',1);
@ob_end_clean();
set_time_limit(0);
?>

<head>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
</head>
<form action="#" method="post" style="text-align:center;margin-top: 150px;">
    <input class="form-control" type="text" placeholder="Paste yelp.com search link" style="width: 30%;left: 35%;position: relative;" name="query" />
    <br>
    <input type="submit" class="btn btn-primary" value="Download results" name="submit_button" />
    <br>
    <br>
    <hr>
    <?php echo "Last query : ".base64_decode(get_option('url'))."<br><br>";?>
    <?php echo "Last result count : ".get_option('current_count')."<br><br>";?>
    <input type="submit" class="btn btn-primary" value="Resume last query" name="resume_button" />
</form>
<?php
global $count;
$count = 0;
require_once('simple_html_dom.php');
require_once('lib/Ouath.php');
// @ini_set('zlib.output_compression', 0);
// @ini_set('implicit_flush', 1);
// @ob_end_clean();

set_time_limit(0);

if(isset($_POST['submit_button']))
{
    clear_prev_results();
    $CONSUMER_KEY       = 'hDdWzPS6tV8OJsYyFXnXZg';
    $CONSUMER_SECRET    = 'aU-rZXP3uHY0k_COucfngAKpQRg';
    $TOKEN              = 'TWhpdx9VzSlbMzEL_cllJ8ufTz5SfTJ0';
    $TOKEN_SECRET       = 'FpO4W7J8RT4VOmiHIYhZOBoeY_8';
    $API_HOST           = 'api.yelp.com';
    // $SEARCH_LIMIT       = 6;
    $SEARCH_PATH        = '/v2/search/';
    $BUSINESS_PATH      = '/v2/business/';
    $url                = $_REQUEST['query'];
    set_option('url', base64_encode($url));
    $neighbourhoods     = get_neighbourhoods($url);
    // dying($neighbourhoods);
    sleep(1);
    echo "Total no of Neighbourhoods".count($neighbourhoods);
    flush();
    $parts              = parse_url($url);
    parse_str($parts['query'], $query);
    $DEFAULT_TERM       = $query['find_desc'];
    $DEFAULT_LOCATION   = $query['find_loc'];
    $loop = 0;
    write_headers_csv_file();
    foreach ($neighbourhoods as $neighbourhood) {
        // if ($loop++ > 1) {
        //     echo "exit";
        //     break;
        // }
        echo "Neighbourhood :".$neighbourhood."<br />";
        $new_location = '';
        $new_location =  $neighbourhood.' ,'.$DEFAULT_LOCATION;
        sleep(3);
        $loop_limit = calculate_total_calls($DEFAULT_TERM, $new_location);
        echo "Loop Loopimit: ".$loop_limit."<br />";
        flush();
        loop_api_calls($loop_limit, $DEFAULT_TERM, $new_location);
    }
    // require_once 'download.php';
} else if(isset($_POST['resume_button'])){
    // clear_prev_results();
    $CONSUMER_KEY       = 'hDdWzPS6tV8OJsYyFXnXZg';
    $CONSUMER_SECRET    = 'aU-rZXP3uHY0k_COucfngAKpQRg';
    $TOKEN              = 'TWhpdx9VzSlbMzEL_cllJ8ufTz5SfTJ0';
    $TOKEN_SECRET       = 'FpO4W7J8RT4VOmiHIYhZOBoeY_8';
    $API_HOST           = 'api.yelp.com';
    // $SEARCH_LIMIT       = 6;
    $SEARCH_PATH        = '/v2/search/';
    $BUSINESS_PATH      = '/v2/business/';
    $url                = base64_decode(get_option('url'));
    $neighbourhoods     = get_neighbourhoods($url);
    // dying($neighbourhoods);
    sleep(1);
    echo "Total no of Neighbourhoods".count($neighbourhoods);
    flush();
    $parts              = parse_url($url);
    parse_str($parts['query'], $query);
    $DEFAULT_TERM       = $query['find_desc'];
    $DEFAULT_LOCATION   = $query['find_loc'];
    $loop = 0;
    // write_headers_csv_file();
   global $prev_count ;
   $prev_count = get_option('current_count');
    foreach ($neighbourhoods as $neighbourhood) {
        // if ($loop++ > 1) {
        //     echo "exit";
        //     break;
        // }
        fecho("Neighbourhood :".$neighbourhood."<br />");
        $new_location = '';
        $new_location =  $neighbourhood.' ,'.$DEFAULT_LOCATION;
        sleep(3);
        $loop_limit = calculate_total_calls($DEFAULT_TERM, $new_location);
        fecho("Loop Loopimit: ".$loop_limit."<br />");
        flush();
        loop_api_calls($loop_limit, $DEFAULT_TERM, $new_location);
    }   
}

function fecho($string) {
 echo $string;
 flush();
 ob_flush();
}

function clear_prev_results(){
    $file = getcwd().'/results.csv';
    $fp = fopen( $file ,'w');
}

function can_resume(){
    
}

function get_neighbourhoods($query){
    sleep(1);
    $html = str_get_html(doCall($query));
    $website = false;
    if (empty($html)) {
        echo "No Website Found <hr>";
        die('Erro : Cannot get neighbourhoods');
    }
    $neighbourhoods = array();
    foreach($html->find('.place input') as $e){
        $result = explode('::', $e->value);
        // dying($result);
        if (empty($result[1])) {
            $result2 = explode(':', $e->value);
            $neighbourhoods[] = $result2[1];
        } else {
            $neighbourhoods[] = $result[1];
        }
    }
    return $neighbourhoods;
}


function request($host, $path) {
    $unsigned_url = "https://" . $host . $path;
    $token = new OAuthToken($GLOBALS['TOKEN'], $GLOBALS['TOKEN_SECRET']);

    // Consumer object built using the OAuth library
    $consumer = new OAuthConsumer($GLOBALS['CONSUMER_KEY'], $GLOBALS['CONSUMER_SECRET']);

    // Yelp uses HMAC SHA1 encoding
    $signature_method = new OAuthSignatureMethod_HMAC_SHA1();

    $oauthrequest = OAuthRequest::from_consumer_and_token(
        $consumer, 
        $token, 
        'GET', 
        $unsigned_url
    );
    
    // Sign the request
    $oauthrequest->sign_request($signature_method, $consumer, $token);
    
    // Get the signed URL
    $signed_url = $oauthrequest->to_url();
   
    // Send Yelp API Call

    try {
       
        $ch = curl_init($signed_url);

        if (FALSE === $ch)

            throw new Exception('Failed to initialize');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);

        if (FALSE === $data)

            throw new Exception(curl_error($ch), curl_errno($ch));
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (200 != $http_status)
            throw new Exception($data, $http_status);

        curl_close($ch);
    } catch(Exception $e) {
        trigger_error(sprintf(
            'Curl failed with error #%d: %s',
            $e->getCode(), $e->getMessage()),
            E_USER_ERROR);
    }
    return $data;
}


function search($term, $location, $offset) {
    $url_params = array();
   
    $url_params['term'] = $term ?: $GLOBALS['DEFAULT_TERM'];
    $url_params['location'] = $location?: $GLOBALS['DEFAULT_LOCATION'];
    // $url_params['location'] = 'Burleith, Washington DC, DC';
    // $url_params['limit'] = $GLOBALS['SEARCH_LIMIT'];
    $url_params['offset'] = $offset;
    // $url_params['l'] = 'p:DC:Washington::Hillcrest';
    $search_path = $GLOBALS['SEARCH_PATH'] . "?" . http_build_query($url_params);
    // echo $search_path."<break>";
    // return false;
    return request($GLOBALS['API_HOST'], $search_path);
}


function get_business($business_id) {
    $business_path = $GLOBALS['BUSINESS_PATH'] . urlencode($business_id);
    return request($GLOBALS['API_HOST'], $business_path);
}

function loop_api_calls($loop_limit, $term, $location){
    $offset = 0;
    if ($loop_limit > 50) {
       $loop_limit = 50;
    }
    
        global $prev_count, $prev_count_extra;
    for ($i=0; $i <$loop_limit ; $i++) {
        if (isset($prev_count) && !empty($prev_count)) {
            $prev_count = $prev_count-20;
            if ($prev_count >= 20) {
                continue;
            } else if ($prev_count < 20 && $prev_count > 0) {
                $prev_count_extra = $prev_count;
            } else if ($prev_count <= 0) {
                continue;
            }
            
        }
        $response = json_decode(search(urldecode($term), urldecode($location), $offset),true);
        $offset += 20;
        $result = loop_results($response['businesses']);
        // if ($result === false) {
        //     return false;
        // }
        // print_r(json_decode($response, true));
        // echo "<pre>";print_r($response);echo "</pre>";
    }
}

function loop_results($results){
    global $count;
    $parent_array = array();
    $limit = 0;

    global $prev_count_extra, $prev_count;

    foreach ($results as $result) {
        if (isset($prev_count_extra) && !empty($prev_count_extra)) {
            $prev_count_extra = $prev_count_extra - 1;
            $prev_count = $prev_count_extra;
            if ($prev_count_extra <= 0) {
                continue;
            }
        }
        if (!empty(get_option('current_count'))) {
            $count = get_option('current_count');    
        }
        set_option('current_count', ++$count);
        $child_array = array();
        $website     = '';
        $email       = '';
        echo 'No of result: '.$count."<br />";
    //    echo "S.No:".++$limit."</br>";
        // if ($limit > 5) {
        //     return false;
        // }
        if (isset($result['name'])) {
            $child_array[] = $result['name'];
            echo "Name :".$result['name']."<br>";
        } else {
            $child_array[] = '';
        }
        if (isset($result['phone'])) {
            $child_array[] = $result['phone'];
            echo "Phone :".$result['phone']."<br>";
        } else {
            $child_array[] = '';
        }
        $website = get_website_from_link($result['url']);
        if ($website) {
            $email = get_email_from_site($website);
        }
	    flush();
	    sleep(2);
        if (isset($website)) {
            $child_array[] = $website;
        } else {
            $child_array[] = '';
        }
        if (isset($email)) {
            $child_array[] = $email;
        } else {
            $child_array[] = '';
        }
        if (isset($result['display_phone'])) {
            $child_array[] = $result['display_phone'];
        } else {
            $child_array[] = '';
        }
        if (isset($result['location']['display_address'])) {
            $str = '';
            foreach ($result['location']['display_address'] as $value) {
                $str = $value.' '.$str;
            }
            $child_array[] = $str;
        } else {
            $child_array[] = '';
        }
        if (isset($result['url'])) {
            $child_array[] = $result['url'];
        } else {
            $child_array[] = '';
        }
        // if (isset($result['rating'])) {
        //     $child_array[] = $result['rating'];
        // } else {
        //     $child_array[] = '';
        // }
        if (isset($result['review_count'])) {
            $child_array[] = $result['review_count'];
        } else {
            $child_array[] = '';
        }
        if (isset($result['is_closed'])) {
            $child_array[] = $result['is_closed'];
        } else {
            $child_array[] = '';
        }
        if (isset($result['location']['coordinate']['latitude'])) {
            $child_array[] = $result['location']['coordinate']['latitude'];
        } else {
            $child_array[] = '';
        }
        if (isset($result['location']['coordinate']['longitude'])) {
            $child_array[] = $result['location']['coordinate']['longitude'];
        } else {
            $child_array[] = '';
        }
        echo "writing results into files...<hr />";
        write_into_csv_file($child_array);
    }
}

function calculate_total_calls($term, $location){
    $response = json_decode(search(urldecode($term), urldecode($location), 0),true);
    // echo "<pre>";
    // print_r($response);
    // echo "</pre>";
    // exit;
    // die($response['total']);
    if (isset($response['total'])) {
        echo "Neighbourhood result count: ".$response['total']."<br />";
        // echo "<pre>";print_r($response);
        $loop_limit = $response['total'] / 20 ;
        return ceil($loop_limit);
    } else {
        return false;
    }
}


function get_website_from_link($link){
    sleep(2);
    include_once('simple_html_dom.php');
    $html = str_get_html(doCall($link));
    $website = false;
    if (empty($html)) {
        echo "No Website Found <hr>";
        return false;
    }
    foreach($html->find('.biz-website a') as $e){
        $website = $e->innertext;
    }
    if ($website) {
        return $website;
    }
    echo "No Website Found <hr>";
    return $website;
}

function get_email_from_site($website){
    echo "Parsing email from main page...<br />";
    if (stripos($website, 'http') === FALSE) {
        $website = 'http://'.$website;
    }
    echo "Website :" . $website."<br>";
    sleep(1);
    $email = parse_email($website);
    if (empty($email)) {
        echo "Deep searching email ...<br />";
        $email = deep_email_search($website);
    }
    if ($email) {
        echo "Email : ".$email . "<br/>";
    } else {
        echo "Email : Not found <br/>";
    }
    return $email;
}

function parse_email($link){
    echo "Parsing : ".$link."<br>";
    $text = doCall($link);
    if (!empty($text)) {
        $res = preg_match_all(
            "/[a-z0-9]+([_\\.-][a-z0-9]+)*@([a-z0-9]+([\.-][a-z0-9]+)*)+\\.[a-z]{2,}/i",
            $text,
            $matches
        );
        if ($res) {
            foreach(array_unique($matches[0]) as $email) {
                return $email;
            }
        }
        else {
            return false;
        }
    }
}

function deep_email_search($website){
    echo "Parsing other pages...<br />";
    sleep(2);
    $html = str_get_html(doCall($website));
    $email = false;
    if (empty($html)) {
        return false;
    }
    $email = false;
    foreach($html->find('a') as $e){
        if (stripos($e->href, 'contact') !== FALSE || stripos($e->href, 'about') !== FALSE || stripos($e->href, 'impressum') !== FALSE) {
            sleep(3);
            $deep_link = make_valid_url($website, $e->href);
            $email = parse_email($deep_link);
            if ($email) {
                break;
            }
        }
    }
    return $email;
}

function make_valid_url($website, $deep_link){
    if (stripos($deep_link, 'http') !== FALSE) {
        echo "valid URL :".$deep_link."</br>";
        return $deep_link;
    }
    $parsed_url = parse_url($deep_link);
    if (empty($parsed_url['host'])) {
        $link = addTrailingSlash($website).removeBeginningSlash($deep_link);
        echo "Formed valid URL :".$link."</br>";
        return $link;
    }
}

function addTrailingSlash($string) {
    return removeTrailingSlash($string) . '/';
}

function removeTrailingSlash($string) {
    return rtrim($string, '/');
}

function removeBeginningSlash($string) {
    return ltrim($string, '/');
}

function write_into_csv_file($data){
    $file = getcwd().'/results.csv';
    $fp = fopen( $file ,'a+');
    // echo "<pre>";
    // print_r($data);
    // echo "</pre>";
    // foreach ($data as $fields) {
    //     echo "<pre>";
    // print_r($fields);
    // echo "</pre>";
    fputcsv($fp, $data);
    // }
    fclose($fp);
}

function write_headers_csv_file(){
    $list = array (
        array('Name', 'Phone', 'Website', 'Email' ,'Display Phone', 'Location Address', 'URL', 'Review Count','Is Closed' ,'Location latitude', 'Location longitude'),
    );

    $file = getcwd().'/results.csv';
    $fp = fopen( $file ,'w');

    foreach ($list as $fields) {
        fputcsv($fp, $fields);
    }

    fclose($fp);
    echo "Headers written successfully";
        flush();

}

function doCall($URL) //Needs a timeout handler
{
    $SSLVerify = false;
    $URL = trim($URL);
    if(stripos($URL, 'https://') !== false){ $SSLVerify = true; }
    $ch = curl_init($URL);
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, ($SSLVerify === true) ? 2 : false );
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $SSLVerify);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, true);
    @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $rawResponse      = curl_exec($ch);
    // echo $rawResponse;
// echo curl_getinfo($ch) . '<br/>';
// echo curl_errno($ch) . '<br/>';
// echo curl_error($ch) . '<br/>';
    curl_close($ch);
    return $rawResponse;
}

function dying($data){
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

function create_db_connection(){
    global $conn;
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "yelp_crawler";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 

 
}

function set_option($option_name, $option_value){
    global $conn;
    $sql = "SELECT * FROM yelp_options WHERE option_name='".$option_name."'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $sql = "UPDATE yelp_options SET option_value='".$option_value."' WHERE option_name='".$option_name."'";
        if ($conn->query($sql) === TRUE) {
            return true;
        } 
    } else {
        $sql = "INSERT INTO yelp_options (option_name, option_value)
        VALUES ('".$option_name."', '".$option_value."')";
        if ($conn->query($sql) === TRUE) {
            return true;
        }    

        
    }
    
}

function get_option($option_name){
    global $conn;
    $sql = "SELECT * FROM yelp_options WHERE option_name='".$option_name."'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // output data of each row
        while($row = $result->fetch_assoc()) {
            // echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
            return $row['option_value'];
        }
    } else {
        return false;
    }
}