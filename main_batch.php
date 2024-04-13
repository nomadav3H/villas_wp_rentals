<?php


$villas = array();




function convert_date($date){

    return $date[5] . $date[6] . '-' . $date[8] . $date[9] . '-' . $date[0] . $date[1] . $date[2] . $date[3] ;
}


function convert_date2($date){
    
    return  $date[6] . $date[7] . $date[8] . $date[9] . "-" . $date[0] . $date[1] . "-" . $date[3] . $date[4] ;
}


function format_dates($date) {
    // Convert the date to the desired format
    return date('m-d-Y', strtotime($date));
}




 function get_price_ranges($data) {

    $prev_rate = null;
    $start_date = null;
    $result = array();

    foreach ($data as $date => $details) {
        $current_rate = $details['rate'];
        $min_nights = $details['stay']['min'];
        
        
        $inner = array('period_min_days_booking' => $min_nights,
        "period_extra_price_per_guest"=> 0.0,
        "period_price_per_weekeend"=> 0.0,
        "period_checkin_change_over" => 0.0,
        "period_checkin_checkout_change_over" => 0.0,
        "period_price_per_month" => 0.0,
        "period_price_per_week" => 0.0,
        
        );

        if ($current_rate != $prev_rate) {
            if ($start_date !== null) {
                array_push($result, array(
                    convert_date($start_date),
                    convert_date($date),
                    (int)$prev_rate,
                    array(),
                    array(),
                    "",
                    10,
                    5,
                    $min_nights,
                ));
            }

            $start_date = $date;
            $prev_rate = $current_rate;
        }
    }

    // Add the last entry if needed
    if ($start_date !== null) {
        array_push($result, array(
            convert_date($start_date),
            convert_date($date),
            (int)$prev_rate,
            array(),
            array(),
            "",
            10,
            5,
            $min_nights,
        ));
    }

    return $result;
}


function createDateRangeArray($strDateFrom, $strDateTo)
{
    $aryRange = [];

    $iDateFrom = strtotime(convert_date2($strDateFrom));
    
    //echo  $iDateFrom . " $strDateFrom $strDateTo<br>";
    
    $iDateTo = strtotime(convert_date2($strDateTo));
    
     //echo  $iDateFrom . " $strDateFrom $strDateTo $iDateTo<br>";

    if ($iDateTo >= $iDateFrom) {
        array_push($aryRange,  $iDateFrom); // first entry
        while ($iDateFrom < $iDateTo) {
            $iDateFrom += 86400; // add 24 hours
            array_push($aryRange, $iDateFrom);
        }
    }

    return $aryRange;
}




foreach($villas as $api_id => $post_id){
    echo "$api_id $post_id  <br>";
    



date_default_timezone_set('America/Merida'); // Adjust the time zone as needed

$curlUrl = "https://cabovillas.trackhs.com/api/pms/units/$api_id/pricing";

$username = "";
$password = "";

$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => $curlUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "Content-Type: application/json",
        "dataType: json",
        "Authorization: Basic " . base64_encode("$username:$password")
    ),
));

$response = curl_exec($curl);
$error = curl_error($curl);

curl_close($curl);

$decodedResponse = json_decode($response, true);





$mega_array = array();






$resultArray = get_price_ranges($decodedResponse['rateTypes'][0]['rates']);


$newData = $decodedResponse['rateTypes'][0]['rates'];

foreach ($newData as $date => $details) {
        $current_rate = $details['rate'];
        $min_nights = $current_rate = $details['stay']['min'];
        $time_String = strtotime($date);

        $inner = array('period_min_days_booking' => $min_nights,
        "period_extra_price_per_guest"=> 0.0,
        "period_price_per_weekeend"=> 0.0,
        "period_checkin_change_over" => 0.0,
        "period_checkin_checkout_change_over" => 0.0,
        "period_price_per_month" => 0.0,
        "period_price_per_week" => 0.0,
        
        );

         $mega_array[strtotime($date)] = $inner;
        
}



$convertedArray = [];

foreach ($resultArray as $dateRange) {
    $startDate = strtotime($dateRange[0]);

    $endDate = strtotime($dateRange[1] . ' 23:59:59'); // Set end time to end of the day
    $value = $dateRange[2];

    $convertedArray[] = [$startDate, $endDate, $value];
}





$inputArray = get_price_ranges($decodedResponse['rateTypes'][0]['rates']);

//var_dump($inputArray);


$outputArray = array();





//$mega_array=array();

//var_dump($inputArray);


foreach ($inputArray as $entry) {
    
    $startTimestamp = strtotime(convert_date2($entry[0])); 
    $endTimestamp = strtotime(convert_date2($entry[1]));
    
    $period = createDateRangeArray($entry[0], $entry[1],  $entry[2] );
    
    foreach ($period as $stuff) {
        $outputArray[$stuff] = floatval($entry[2]) ;
    }

}



//this array will have to be used to update the mega 
update_post_meta($post_id, 'mega_details', $mega_array );
//var_dump($mega_array);



// this array will have to be used for update_post_meta for custom price 
update_post_meta($post_id, 'custom_price', $outputArray );
//var_dump($outputArray);


// this array will have to be used in the update post meta for more rooms mod 
update_post_meta($post_id, 'moreRoomsmod', $resultArray );
//var_dump($resultArray);


}






?>
