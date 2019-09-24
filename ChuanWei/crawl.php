<?php
ini_set("max_execution_time", "300");
date_default_timezone_set('Asia/Taipei');
echo '開始時間:'.date("d-m-Y H:i:s"."\n");   //開始時間


$ch = curl_init();

curl_setopt($ch,CURLOPT_URL,"https://www.ambassador.com.tw/home");   //抓國賓電影首頁
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
        $movies[$index]["movie_name"] = $movieName->nodeValue;
       
    }

    $dataId = $xpath->evaluate("./@data-id",$entry);   //可以抓data-id，將用做後面抓日期用

    foreach($dataId as $id){
        $movies[$index]["movie_ID"] = $id->nodeValue;
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

$index = 0; //用在movies、movieDay和movieTime
$indexDay = 0;  //用在movieDay

$indexArray = 0;    //用在movieTime
$indexTimeToSeat = 0;   //用在movieTime
$movieDay = [];

error_reporting(E_ALL^E_NOTICE);    //暫時關掉NOTICE

// 根據各個電影的網址抓資料
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
        $movieInfo = $xpath->query("./h6",$entry);  
        $movies[$index]["movie_enname"]=$movieInfo[0]->nodeValue;

        $movieInfo = $xpath->query("./div/span",$entry);  
        $movies[$index]["rating"]=$movieInfo[0]->nodeValue;
        $movies[$index]["run_time"]=$movieInfo[1]->nodeValue;
        
        $movieInfo = $xpath->query("./p",$entry);  
        $movies[$index]["info"]=$movieInfo[0]->nodeValue;
        $movies[$index]["actor"]=$movieInfo[1]->nodeValue;
        $movies[$index]["genre"]=$movieInfo[2]->nodeValue;
        $movies[$index]["play_date"]=$movieInfo[3]->nodeValue;
        
    }
    $entries = $xpath->query('//*[@id="search-bar-page"]/div/div/div[1]/ul/li/ul/li');   //抓電影介紹
    foreach($entries as $entry){
        $explodeDay = explode(", ",$entry->nodeValue);
        
        $movieDay[$indexDay]["movie_ID"] =$movies[$index]["movie_ID"];
        $movieDay[$indexDay]["weekday"] = $explodeDay[0];
        $movieDay[$indexDay]["date"] = $explodeDay[1];
        $indexDay++;
    }

    $entries = $xpath->query('//*[@id="clip-play-1"]/div/iframe');   //抓電影預告片
    $entry = $entries[0];
    $movieTrailers = $xpath->evaluate("./@src",$entry);
    $movies[$index]["trailer"] =$movieTrailers[0]->nodeValue;

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
                $movieTime[$indexArray]["movie_ID"]=$movies[$index]["movie_ID"];
                $movieTime[$indexArray]["theater_name"]=$theaterName;
                $movieTime[$indexArray]["seat_tag"]=$seatTag;
                $movieTime[$indexArray]["time"]=$mt->nodeValue;
                $indexArray++;
            }
        $movieTheater = $xpath->query("./ul[$i]/li/p/span",$entry);

        foreach($movieTheater as $mt){
                // var_dump($mt->nodeValue);   //該影城提供的位子
                if($mt->nodeValue){     //發現有時候會取到空的，所以多個判斷
                    $movieTime[$indexTimeToSeat]["seat_info"]=$mt->nodeValue;
                    $indexTimeToSeat++;
                }
            }
            
        }

    }
    $index++;
    // if($index>0) break;
}
// var_dump($movies);

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
        $theaters[$index]["theater_name"]=$theaterName[0]->nodeValue;    

        $theaterAddr = $xpath->query('./a/div[2]/p[1]',$entry);     //影城地址
        $theaters[$index]["address"]=$theaterAddr[0]->nodeValue;
        
        $theaterPhone = $xpath->query('./a/div[2]/p[2]',$entry);    //影城電話
        $theaters[$index]["phone"]=$theaterPhone[0]->nodeValue;
    }
    
    $index++;
}
// var_dump($theaters);



//抓即將上映的電影
$ch = curl_init();
    
curl_setopt($ch,CURLOPT_URL,"https://www.ambassador.com.tw/home/MovieList?Type=0");   //抓即將上映的網頁
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch,CURLOPT_HEADER,0);

$output = curl_exec($ch);

curl_close($ch);
$doc = new DOMDocument();
libxml_use_internal_errors(true);
$doc->loadHTML($output);

$xpath = new DOMXPath($doc);
//結束起手


$hrefs = [];
$index = 0;
$comingMovies = [];
$entries = $xpath->query('//*[@id="tab2"]/div');
foreach($entries as $entry){
    $movieHrefs = $xpath->evaluate('./div/a/@href',$entry);
    foreach($movieHrefs as $mh){
        array_push($hrefs,"https://www.ambassador.com.tw".$mh->nodeValue);
        $comingMovies[$index]["movie_ID"]=
        substr($mh->nodeValue,strrpos($mh->nodeValue,"MID=")+4,(strrpos($mh->nodeValue,"&")-strrpos($mh->nodeValue,"MID=")-4));
        $index++;
    }


    $index = 0;
    $moviePosters = $xpath->evaluate('./div/a/img/@src',$entry);
    foreach($moviePosters as $mp){
        $comingMovies[$index]["poster"]=$mp->nodeValue;
        $index++;
    }

    $index = 0;
    $movieTitles = $xpath->query('./div/div/div/h6',$entry);
    foreach($movieTitles as $mt){
        $comingMovies[$index]["movie_name"]=$mt->nodeValue;
        $index++;
    }

    $index = 0;
    $movieEnTitle = $xpath->query('./div/div/div/p',$entry);
    foreach($movieEnTitle as $met){
        $comingMovies[$index]["movie_enname"]=$met->nodeValue;
        $index++;
    }

    // var_dump($comingMovies);
}

$index = 0;
foreach($hrefs as $h){
    $ch = curl_init();
    
    curl_setopt($ch,CURLOPT_URL,$h);   //抓該電影的資料
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_HEADER,0);
    
    $output = curl_exec($ch);
    
    curl_close($ch);
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($output);
    
    $xpath = new DOMXPath($doc);
    //起手完畢

    $entries = $xpath->query('//*[@id="movie-info"]/div/div/div[3]');
    $entry = $entries[0];
    $infos = $xpath->query('./p',$entry);
    $comingMovies[$index]["info"]=$infos[0]->nodeValue;
    $comingMovies[$index]["actor"]=$infos[1]->nodeValue;
    $comingMovies[$index]["genre"]=$infos[2]->nodeValue;
    $comingMovies[$index]["play_date"]=$infos[3]->nodeValue;
    
    $entries = $xpath->query('//*[@id="clip-play-1"]/div/iframe');
    $entry = $entries[0];
    $infos = $xpath->evaluate('./@src',$entry);
    
    $comingMovies[$index]["trailer"]=$infos[0]->nodeValue;
    $index++;
    // if($index>0) break;
}

//到這邊停抓即將上映


//連接資料庫

$dbLink = @mysqli_connect("localhost", "root", "") or die(mysqli_connect_error());
mysqli_query($dbLink, "set names utf8");
mysqli_select_db($dbLink, "ambassador");


// 將電影資料存進資料庫
   
$truncateText = "truncate table movies";    //清空movies表
mysqli_query($dbLink, $truncateText); 

foreach($movies as $index => $cm){  //避免文字裡有'這個符號
    $movies[$index]['movie_enname'] = str_replace("'","\'",$movies[$index]['movie_enname']);
}

$moviesText = "('{$movies[0]['movie_name']}', '{$movies[0]['movie_enname']}' , '{$movies[0]['movie_ID']}', '{$movies[0]['rating']}', '{$movies[0]['run_time']}' ,'{$movies[0]['info']}'
         , '{$movies[0]['actor']}' , '{$movies[0]['genre']}', '{$movies[0]['play_date']}', '{$movies[0]['poster']}', '{$movies[0]['trailer']}')";

for($i = 1;$i<count($movies);$i++){
    $moviesText .= ", ('{$movies[$i]['movie_name']}', '{$movies[$i]['movie_enname']}' , '{$movies[$i]['movie_ID']}', '{$movies[$i]['rating']}', '{$movies[$i]['run_time']}', '{$movies[$i]['info']}'
              , '{$movies[$i]['actor']}' , '{$movies[$i]['genre']}', '{$movies[$i]['play_date']}', '{$movies[$i]['poster']}', '{$movies[$i]['trailer']}')";
    
}
        $insertMovies = "insert into movies (movie_name,movie_enname, movie_ID, rating, run_time, info, actor, genre, play_date, poster, trailer) Values ".$moviesText;
        mysqli_query($dbLink, $insertMovies);    //存進movies

//存電影時間
$truncateText = "truncate table movietime";    //清空movies表
mysqli_query($dbLink, $truncateText); 


$moviesText = "('{$movieTime[0]['movie_ID']}' , '{$movieTime[0]['theater_name']}',
                '{$movieTime[0]['seat_tag']}', '{$movieTime[0]['time']}' ,'{$movieTime[0]['seat_info']}')";

for($i = 1;$i<count($movieTime);$i++){
    $moviesText .= ", ('{$movieTime[$i]['movie_ID']}' , '{$movieTime[$i]['theater_name']}',
                       '{$movieTime[$i]['seat_tag']}', '{$movieTime[$i]['time']}' ,'{$movieTime[$i]['seat_info']}')";
    
}
        $insertMovieTime = "insert into movietime (movie_ID, theater_name, seat_tag, time, seat_info) Values ".$moviesText;
        mysqli_query($dbLink, $insertMovieTime);    //存進movietime

//存電影日期
$truncateText = "truncate table movieday";    //清空movies表
mysqli_query($dbLink, $truncateText); 


$moviesText = "('{$movieDay[0]['movie_ID']}' , '{$movieDay[0]['weekday']}', '{$movieDay[0]['date']}')";

for($i = 1;$i<count($movieDay);$i++){
    $moviesText .= ", ('{$movieDay[$i]['movie_ID']}' , '{$movieDay[$i]['weekday']}', '{$movieDay[$i]['date']}')";
}
        $insertMovieDay = "insert into movieday (movie_ID, weekday, date) Values ".$moviesText;
        mysqli_query($dbLink, $insertMovieDay);    //存進movietime

//存影城
$truncateText = "truncate table theaters";    //清空movies表
mysqli_query($dbLink, $truncateText); 


$theaterText = "('{$theaters[0]['theater_name']}' , '{$theaters[0]['address']}', '{$theaters[0]['phone']}', '{$theaters[0]['img']}')";

for($i = 1;$i<count($theaters);$i++){
    $theaterText .= ", ('{$theaters[$i]['theater_name']}' , '{$theaters[$i]['address']}', '{$theaters[$i]['phone']}', '{$theaters[$i]['img']}')";
}
        $insertTheaters = "insert into theaters (theater_name, address, phone, img) Values ".$theaterText;
        mysqli_query($dbLink, $insertTheaters);    //存進theaters表

// 存即將上映的電影
$truncateText = "truncate table comingMovies";    //清空comingMovies表
mysqli_query($dbLink, $truncateText); 

foreach($comingMovies as $index => $cm){
    $comingMovies[$index]['movie_enname'] = str_replace("'","\'",$comingMovies[$index]['movie_enname']);
}


$comingMoviesText = "('{$comingMovies[0]['movie_name']}' , '{$comingMovies[0]['movie_enname']}',
                      '{$comingMovies[0]['movie_ID']}', '{$comingMovies[0]['info']}',
                      '{$comingMovies[0]['actor']}', '{$comingMovies[0]['genre']}',
                      '{$comingMovies[0]['play_date']}', '{$comingMovies[0]['poster']}',
                      '{$comingMovies[0]['trailer']}')";

for($i = 1;$i<count($comingMovies);$i++){
    $comingMoviesText .= ", ('{$comingMovies[$i]['movie_name']}' , '{$comingMovies[$i]['movie_enname']}',
                        '{$comingMovies[$i]['movie_ID']}', '{$comingMovies[$i]['info']}',
                        '{$comingMovies[$i]['actor']}', '{$comingMovies[$i]['genre']}',
                        '{$comingMovies[$i]['play_date']}', '{$comingMovies[$i]['poster']}',
                        '{$comingMovies[$i]['trailer']}')";
}
        $insertComingMovies = "insert into comingMovies (movie_name, movie_enname, movie_ID,
        info, actor, genre, play_date, poster, trailer) Values ".$comingMoviesText;

        // echo($insertComingMovies);

        mysqli_query($dbLink, $insertComingMovies);    //存進comingMovies表

mysqli_close($dbLink);
echo '結束時間:'.date("d-m-Y H:i:s");   //結束時間

?>
