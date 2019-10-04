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



$movies = [];
$hrefs = [];


$index=0;   //在這邊宣告為全域變數，才可以在不同foreach裡面操作
$entries = $xpath->query('//*[@id="moveList"]/li');   //用XPATH抓國賓電影清單
foreach($entries as $entry){
   
    $movieNameList = $xpath->query("./a",$entry);  

    foreach($movieNameList as $movieName){
        $movies[$index]["name"] = $movieName->nodeValue;
       
    }

    $dataId = $xpath->evaluate("./@data-id",$entry);   //可以抓data-id，將用做後面抓日期用

    foreach($dataId as $id){
        $movies[$index]["encoded_id"] = $id->nodeValue;
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

$index = 0; //用在movies、movie_day和movie_time
$indexDay = 0;  //用在movie_day

$indexArray = 0;    //用在movie_time
$indexTimeToSeat = 0;   //用在movie_time
$movie_day = [];

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
        $movies[$index]["enname"]=$movieInfo[0]->nodeValue;

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
        
        $movie_day[$indexDay]["movies_encoded_id"] =$movies[$index]["encoded_id"];
        $movie_day[$indexDay]["weekday"] = $explodeDay[0];
        $movie_day[$indexDay]["date"] = $explodeDay[1];
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
        // $movie_time[$index]["theaterName"] = $mt->nodeValue;
        $theaterName = $mt->nodeValue;
    }


    $movieTheater = $xpath->query("./p",$entry);


    for($i=1;$i<count($movieTheater)+1;$i++){
        $movieTheater = $xpath->query("./p",$entry);
        $seatTag = $movieTheater[$i-1]->nodeValue;

        $movieTheater = $xpath->query("./ul[$i]/li/h6",$entry);
        foreach($movieTheater as $mt){
                // var_dump($mt->nodeValue);   //該影城提供的時刻
                $movie_time[$indexArray]["movies_encoded_id"]=$movies[$index]["encoded_id"];
                $movie_time[$indexArray]["theaters_name"]=$theaterName;
                $movie_time[$indexArray]["seat_tag"]=$seatTag;
                $movie_time[$indexArray]["time"]=$mt->nodeValue;
                $indexArray++;
            }
        $movieTheater = $xpath->query("./ul[$i]/li/p/span",$entry);

        foreach($movieTheater as $mt){
                // var_dump($mt->nodeValue);   //該影城提供的位子
                if($mt->nodeValue){     //發現有時候會取到空的，所以多個判斷
                    $movie_time[$indexTimeToSeat]["seat_info"]=$mt->nodeValue;
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
        $theaters[$index]["imgurl"]="https://www.ambassador.com.tw". substr($ti->nodeValue,2);     //抓到的地址是相對位址
    }
    $theaterName = $xpath->query('./a/div[2]/h6',$entry);   //影城名稱
    if($theaterName[0]){    //會抓到幽靈，所以這邊加判斷
        $theaters[$index]["name"]=$theaterName[0]->nodeValue;    

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
$coming_movies = [];
$entries = $xpath->query('//*[@id="tab2"]/div');
foreach($entries as $entry){
    $movieHrefs = $xpath->evaluate('./div/a/@href',$entry);
    foreach($movieHrefs as $mh){
        array_push($hrefs,"https://www.ambassador.com.tw".$mh->nodeValue);
        $coming_movies[$index]["encoded_id"]=
        substr($mh->nodeValue,strrpos($mh->nodeValue,"MID=")+4,(strrpos($mh->nodeValue,"&")-strrpos($mh->nodeValue,"MID=")-4));
        $index++;
    }


    $index = 0;
    $moviePosters = $xpath->evaluate('./div/a/img/@src',$entry);
    foreach($moviePosters as $mp){
        $coming_movies[$index]["poster"]=$mp->nodeValue;
        $index++;
    }

    $index = 0;
    $movieTitles = $xpath->query('./div/div/div/h6',$entry);
    foreach($movieTitles as $mt){
        $coming_movies[$index]["name"]=$mt->nodeValue;
        $index++;
    }

    $index = 0;
    $movieEnTitle = $xpath->query('./div/div/div/p',$entry);
    foreach($movieEnTitle as $met){
        $coming_movies[$index]["enname"]=$met->nodeValue;
        $index++;
    }

    // var_dump($coming_movies);
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
    $coming_movies[$index]["info"]=$infos[0]->nodeValue;
    $coming_movies[$index]["actor"]=$infos[1]->nodeValue;
    $coming_movies[$index]["genre"]=$infos[2]->nodeValue;
    $coming_movies[$index]["play_date"]=$infos[3]->nodeValue;
    
    $entries = $xpath->query('//*[@id="clip-play-1"]/div/iframe');
    $entry = $entries[0];
    $infos = $xpath->evaluate('./@src',$entry);
    
    $coming_movies[$index]["trailer"]=$infos[0]->nodeValue;
    $index++;
    // if($index>0) break;
}

//到這邊停抓即將上映


//連接資料庫

$conn = @mysqli_connect("ah-zheng.com", "ahzheng_cy_cinemas", "cy_cinemas") or die(mysqli_connect_error());
mysqli_query($conn, "set names utf8");
mysqli_select_db($conn, "ahzheng_cy_cinemas");


// 將電影資料存進資料庫
   
$truncateText = "truncate table movies";    //清空movies表
mysqli_query($conn, $truncateText); 

foreach($movies as $index => $cm){  //避免文字裡有'這個符號
    $movies[$index]['enname'] = str_replace("'","\'",$movies[$index]['enname']);
}

$moviesText = "('{$movies[0]['name']}', '{$movies[0]['enname']}' , '{$movies[0]['encoded_id']}', '{$movies[0]['rating']}', '{$movies[0]['run_time']}' ,'{$movies[0]['info']}'
         , '{$movies[0]['actor']}' , '{$movies[0]['genre']}', '{$movies[0]['play_date']}', '{$movies[0]['poster']}', '{$movies[0]['trailer']}')";

for($i = 1;$i<count($movies);$i++){
    $moviesText .= ", ('{$movies[$i]['name']}', '{$movies[$i]['enname']}' , '{$movies[$i]['encoded_id']}', '{$movies[$i]['rating']}', '{$movies[$i]['run_time']}', '{$movies[$i]['info']}'
              , '{$movies[$i]['actor']}' , '{$movies[$i]['genre']}', '{$movies[$i]['play_date']}', '{$movies[$i]['poster']}', '{$movies[$i]['trailer']}')";
    
}
        $insertMovies = "insert into movies (name,enname, encoded_id, rating, run_time, info, actor, genre, play_date, poster, trailer) Values ".$moviesText;
        mysqli_query($conn, $insertMovies);    //存進movies

//存電影時間
$truncateText = "truncate table movie_time";    //清空movies表
mysqli_query($conn, $truncateText); 


$moviesText = "('{$movie_time[0]['movies_encoded_id']}' , '{$movie_time[0]['theaters_name']}',
                '{$movie_time[0]['seat_tag']}', '{$movie_time[0]['time']}' ,'{$movie_time[0]['seat_info']}')";

for($i = 1;$i<count($movie_time);$i++){
    $moviesText .= ", ('{$movie_time[$i]['movies_encoded_id']}' , '{$movie_time[$i]['theaters_name']}',
                       '{$movie_time[$i]['seat_tag']}', '{$movie_time[$i]['time']}' ,'{$movie_time[$i]['seat_info']}')";
    
}
        $insertmovie_time = "insert into movie_time (movies_encoded_id, theaters_name,seat_tag, time, seat_info) Values ".$moviesText;
        mysqli_query($conn, $insertmovie_time);    //存進movie_time

//存電影日期
$truncateText = "truncate table movie_day";    //清空movies表
mysqli_query($conn, $truncateText); 


$moviesText = "('{$movie_day[0]['movies_encoded_id']}' , '{$movie_day[0]['weekday']}', '{$movie_day[0]['date']}')";

for($i = 1;$i<count($movie_day);$i++){
    $moviesText .= ", ('{$movie_day[$i]['movies_encoded_id']}' , '{$movie_day[$i]['weekday']}', '{$movie_day[$i]['date']}')";
}
        $insertmovie_day = "insert into movie_day (movies_encoded_id, weekday, date) Values ".$moviesText;
        mysqli_query($conn, $insertmovie_day);    //存進movie_time

//存影城
$truncateText = "truncate table theaters";    //清空movies表
mysqli_query($conn, $truncateText); 


$theaterText = "('{$theaters[0]['name']}' , '{$theaters[0]['address']}', '{$theaters[0]['phone']}', '{$theaters[0]['imgurl']}')";

for($i = 1;$i<count($theaters);$i++){
    $theaterText .= ", ('{$theaters[$i]['name']}' , '{$theaters[$i]['address']}', '{$theaters[$i]['phone']}', '{$theaters[$i]['imgurl']}')";
}
        $insertTheaters = "insert into theaters (name, address, phone, imgurl) Values ".$theaterText;
        mysqli_query($conn, $insertTheaters);    //存進theaters表

// 存即將上映的電影
foreach($coming_movies as $index => $cm){
    $coming_movies[$index]['enname'] = str_replace("'","\'",$coming_movies[$index]['enname']);
}


$coming_moviesText = "('{$coming_movies[0]['name']}' , '{$coming_movies[0]['enname']}',
                      '{$coming_movies[0]['encoded_id']}', '{$coming_movies[0]['info']}',
                      '{$coming_movies[0]['actor']}', '{$coming_movies[0]['genre']}',
                      '{$coming_movies[0]['play_date']}', '{$coming_movies[0]['poster']}',
                      '{$coming_movies[0]['trailer']}')";

for($i = 1;$i<count($coming_movies);$i++){
    $coming_moviesText .= ", ('{$coming_movies[$i]['name']}' , '{$coming_movies[$i]['enname']}',
                        '{$coming_movies[$i]['encoded_id']}', '{$coming_movies[$i]['info']}',
                        '{$coming_movies[$i]['actor']}', '{$coming_movies[$i]['genre']}',
                        '{$coming_movies[$i]['play_date']}', '{$coming_movies[$i]['poster']}',
                        '{$coming_movies[$i]['trailer']}')";
}
        $insertcoming_movies = "insert into movies (name, enname, encoded_id,
        info, actor, genre, play_date, poster, trailer) Values ".$coming_moviesText;

        // echo($insertcoming_movies);

        mysqli_query($conn, $insertcoming_movies);    //存進coming_movies表

mysqli_close($conn);
echo "\n".'結束時間:'.date("d-m-Y H:i:s");   //結束時間

?>
