<?php
ini_set("max_execution_time", "300");
date_default_timezone_set('Asia/Taipei');
echo '開始時間:'.date("d-m-Y H:i:s"."\n");   //開始時間


$ch = curl_init();

curl_setopt($ch,CURLOPT_URL,"https://www.ambassador.com.tw/home#!");   //抓國賓電影首頁
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch,CURLOPT_HEADER,0);

$output = curl_exec($ch);

curl_close($ch);
$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML($output);

$xpath = new DOMXPath($doc);
//以上起手式，有要加上其他設定再說(例如：POST)


$dayList = [];
$movies = [];
$hrefs = [];


$index=0;   //在這邊宣告為全域變數，才可以在不同foreach裡面操作
$entries = $xpath->query('//*[@id="moveList"]/li');   //用XPATH抓國賓電影清單
foreach($entries as $entry){
   
    $movieNameList = $xpath->query("./a",$entry);  

    foreach($movieNameList as $movieName){
        $movies[$index]["movieName"] = $movieName->nodeValue;
       
    }

    $dataId = $xpath->evaluate("./@data-id",$entry);   //可以抓data-id，將用做後面抓日期用

    foreach($dataId as $id){
        $movies[$index]["movieId"] = $id->nodeValue;
        $index++;   //!!!!index+1次即可
    }
}

$index = 0;
$entries = $xpath->query('//*[@id="tab1"]/div[3]/div/div');   //找電影的連結和封面
foreach($entries as $entry){
    $movieHrefs = $xpath->evaluate('./a/@href',$entry);
    foreach($movieHrefs as $mh){
        array_push($hrefs,$mh->nodeValue);
    }
    
    $movieImgs = $xpath->evaluate('./a/img/@src',$entry);
    foreach($movieImgs as $mi){
        $movies[$index]["poster"] =$mi->nodeValue; 
        $index++;
    }
}

// var_dump($hrefs);


$index = 0; //用在movies、movieDay和movieTime
$indexDay = 0;  //用在movieDay

$indexArray = 0;    //用在movieTime
$indexTimeToSeat = 0;   //用在movieTime
$movieDay = [];

error_reporting(E_ALL^E_NOTICE);    //暫時關掉NOTICE

//根據各個電影的網址抓資料
foreach($hrefs as $h){
    $ch = curl_init();
    
    curl_setopt($ch,CURLOPT_URL,"https://www.ambassador.com.tw".$h);   //抓該電影的資料
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_HEADER,0);
    
    $output = curl_exec($ch);
    
    curl_close($ch);
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($output);
    
    $xpath = new DOMXPath($doc);
    //起手完畢
    $entries = $xpath->query('//*[@id="movie-info"]/div/div/div[3]');   //抓電影介紹
        
    foreach($entries as $entry){
        $movieInfo = $xpath->query("./div/span",$entry);  
        $movies[$index]["rating"]=$movieInfo[0]->nodeValue;
        $movies[$index]["runTime"]=$movieInfo[1]->nodeValue;
        
        $movieInfo = $xpath->query("./p",$entry);  
        $movies[$index]["info"]=$movieInfo[0]->nodeValue;
        $movies[$index]["actor"]=$movieInfo[1]->nodeValue;
        $movies[$index]["genre"]=$movieInfo[2]->nodeValue;
        $movies[$index]["playDate"]=$movieInfo[3]->nodeValue;
        
    }
    $entries = $xpath->query('//*[@id="search-bar-page"]/div/div/div[1]/ul/li/ul/li');   //抓電影介紹
    foreach($entries as $entry){
        $explodeDay = explode(", ",$entry->nodeValue);
        
        $movieDay[$indexDay]["movieId"] =$movies[$index]["movieId"];
        $movieDay[$indexDay]["weekday"] = $explodeDay[0];
        $movieDay[$indexDay]["date"] = $explodeDay[1];
        $indexDay++;
    }


// 在該電影的網頁中抓在不同影城的播映時間
$entries = $xpath->query('//*[@class="theater-list"]/div/div');   //抓上映影城

foreach($entries as $entry){
    $movieTheater = $xpath->query("./h3/a",$entry);

    $theaterName = "";  //暫存影城名稱

    foreach($movieTheater as $mt){
        // var_dump($mt->nodeValue);   //有上映的影城名稱
        // $movieTime[$index]["theaterName"] = $mt->nodeValue;
        $theaterName = $mt->nodeValue;
    }


    $movieTheater = $xpath->query("./p",$entry);


    for($i=1;$i<count($movieTheater)+1;$i++){
        $movieTheater = $xpath->query("./p",$entry);
        $seatTag = $movieTheater[$i-1]->nodeValue;

        $movieTheater = $xpath->query("./ul[$i]/li/h6",$entry);
        foreach($movieTheater as $mt){
                // var_dump($mt->nodeValue);   //該影城提供的時刻
                $movieTime[$indexArray]["movieId"]=$movies[$index]["movieId"];
                $movieTime[$indexArray]["theaterName"]=$theaterName;
                $movieTime[$indexArray]["seatTag"]=$seatTag;
                $movieTime[$indexArray]["time"]=$mt->nodeValue;
                $indexArray++;
            }
        $movieTheater = $xpath->query("./ul[$i]/li/p/span",$entry);

        foreach($movieTheater as $mt){
                // var_dump($mt->nodeValue);   //該影城提供的位子
                if($mt->nodeValue){     //發現有時候會取到空的，所以多個判斷
                    $movieTime[$indexTimeToSeat]["seatInfo"]=$mt->nodeValue;
                    $indexTimeToSeat++;
                }
            }
            
        }

    }
    $index++;
}

$index = 0;
$theaters = [];
//開抓影城的資料
$ch = curl_init();
    
curl_setopt($ch,CURLOPT_URL,"https://www.ambassador.com.tw/home/TheaterList");   //抓影城的網頁
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch,CURLOPT_HEADER,0);

$output = curl_exec($ch);

curl_close($ch);
$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML($output);

$xpath = new DOMXPath($doc);
$entries = $xpath->query('//*[@class="cell"]');   //抓影城資料

foreach($entries as $entry){
    $theaterImg = $xpath->evaluate('./a/div[1]/span/img/@src',$entry);
    foreach($theaterImg as $ti){
        // var_dump($ti);
        $theaters[$index]["img"]="https://www.ambassador.com.tw". substr($ti->nodeValue,2);     //抓到的地址是相對位址
    }
    $theaterName = $xpath->query('./a/div[2]/h6',$entry);   //影城名稱
    if($theaterName[0]){    //會抓到幽靈，所以這邊加判斷
        $theaters[$index]["theaterName"]=$theaterName[0]->nodeValue;    

        $theaterAddr = $xpath->query('./a/div[2]/p[1]',$entry);     //影城地址
        $theaters[$index]["address"]=$theaterAddr[0]->nodeValue;
        
        $theaterPhone = $xpath->query('./a/div[2]/p[2]',$entry);    //影城電話
        $theaters[$index]["phone"]=$theaterPhone[0]->nodeValue;
    }
    
    $index++;
}
// var_dump($theaters);



//連接資料庫

$dbLink = @mysqli_connect("localhost", "root", "") or die(mysqli_connect_error());
mysqli_query($dbLink, "set names utf8");
mysqli_select_db($dbLink, "ambassador");


// 將電影資料存進資料庫
   
$truncateText = "truncate table movies";    //清空movies表
mysqli_query($dbLink, $truncateText); 


$moviesText = "('{$movies[0]['movieName']}' , '{$movies[0]['movieId']}', '{$movies[0]['rating']}', '{$movies[0]['runTime']}' ,'{$movies[0]['info']}'
         , '{$movies[0]['actor']}' , '{$movies[0]['genre']}', '{$movies[0]['playDate']}', '{$movies[0]['poster']}')";

for($i = 1;$i<count($movies);$i++){
    $moviesText .= ", ('{$movies[$i]['movieName']}' , '{$movies[$i]['movieId']}', '{$movies[$i]['rating']}', '{$movies[$i]['runTime']}', '{$movies[$i]['info']}'
              , '{$movies[$i]['actor']}' , '{$movies[$i]['genre']}', '{$movies[$i]['playDate']}', '{$movies[$i]['poster']}')";
    
}
        $insertMovies = "insert into movies (movieName, movieId, rating, runTime, info, actor, genre, playDate, poster) Values ".$moviesText;
        mysqli_query($dbLink, $insertMovies);    //存進movies

//存電影時間
$truncateText = "truncate table movietime";    //清空movies表
mysqli_query($dbLink, $truncateText); 


$moviesText = "('{$movieTime[0]['movieId']}' , '{$movieTime[0]['theaterName']}',
                '{$movieTime[0]['seatTag']}', '{$movieTime[0]['time']}' ,'{$movieTime[0]['seatInfo']}')";

for($i = 1;$i<count($movieTime);$i++){
    $moviesText .= ", ('{$movieTime[$i]['movieId']}' , '{$movieTime[$i]['theaterName']}',
                       '{$movieTime[$i]['seatTag']}', '{$movieTime[$i]['time']}' ,'{$movieTime[$i]['seatInfo']}')";
    
}
        $insertMovieTime = "insert into movietime (movieId, theaterName, seatTag, time, seatInfo) Values ".$moviesText;
        mysqli_query($dbLink, $insertMovieTime);    //存進movietime

//存電影日期
$truncateText = "truncate table movieday";    //清空movies表
mysqli_query($dbLink, $truncateText); 


$moviesText = "('{$movieDay[0]['movieId']}' , '{$movieDay[0]['weekday']}', '{$movieDay[0]['date']}')";

for($i = 1;$i<count($movieDay);$i++){
    $moviesText .= ", ('{$movieDay[$i]['movieId']}' , '{$movieDay[$i]['weekday']}', '{$movieDay[$i]['date']}')";
}
        $insertMovieDay = "insert into movieday (movieId, weekday, date) Values ".$moviesText;
        mysqli_query($dbLink, $insertMovieDay);    //存進movietime

//存影城
$truncateText = "truncate table theaters";    //清空movies表
mysqli_query($dbLink, $truncateText); 


$theaterText = "('{$theaters[0]['theaterName']}' , '{$theaters[0]['address']}', '{$theaters[0]['phone']}', '{$theaters[0]['img']}')";

for($i = 1;$i<count($theaters);$i++){
    $theaterText .= ", ('{$theaters[$i]['theaterName']}' , '{$theaters[$i]['address']}', '{$theaters[$i]['phone']}', '{$theaters[$i]['img']}')";
}
        $insertTheaters = "insert into theaters (theaterName, address, phone, img) Values ".$theaterText;
        mysqli_query($dbLink, $insertTheaters);    //存進movietime


echo '結束時間:'.date("d-m-Y H:i:s");   //結束時間

mysqli_close($dbLink);
?>
