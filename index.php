<?php
require_once 'connection.php';
session_start();
$dbh = new PDO('mysql:host='.$host.';dbname='.$database.';charset=utf8', $user, $password);
if (!isset($_SESSION['id'])) {
    $_SESSION['id'] = session_id();
}
if ($_SERVER['CONTENT_TYPE']=='application/json'){
    $_POST = json_decode(file_get_contents('php://input'), true);
};
if($_SERVER['REQUEST_METHOD'] == 'POST') {

    if($_POST['type']=='createNews'){
        $data = file_get_contents("php://input");
        if ($_FILES['file']['error'] =='UPLOAD_ERR_OK') {
            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $name = md5(uniqid('', true)).'.'.$ext;
            move_uploaded_file($_FILES['file']['tmp_name'], 'img/'.$name);
            $url_pic = $name;
        }
        else { 
            $url_pic = NULL;
        }
        $sql = "INSERT INTO `news` (`title`, `description`, `text`, `heading`, `date`, `url_pic`) VALUES (:title, :description, :text, :heading, :date, :url_pic) ";
        $sth = $dbh->prepare($sql) ;
        if($_POST['heading']==''){
          $_POST['heading']=null;
        }
        if ($sth->execute(array('title'=>$_POST['title'], 'description' =>$_POST['description'], 'text' =>$_POST['text'], 'heading'=>$_POST['heading'], 'date'=>$_POST['date'], 'url_pic'=>$url_pic))) {
            $sql = "SELECT news.*, count(likes.id) as likes FROM news left JOIN likes on likes.news_id=news.id GROUP BY news.id order by news.date DESC";
            $sth = $dbh->prepare($sql) ;
            $sth->execute();
            $data=$sth->fetchAll(PDO::FETCH_ASSOC);
        }
        else {
        print_r($dbh->errorInfo());
        }
        }
    if($_POST['type']=='changeNews'){
        if ($_FILES['file']['error'] =='UPLOAD_ERR_OK') {
            if ($_POST['url_pic']!=''){
              unlink("./img/{$_POST['url_pic']}");
              print("файл {$_POST['url_pic'] } удален");
            }
            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $name = md5(uniqid('', true)).'.'.$ext;
            move_uploaded_file($_FILES['file']['tmp_name'], 'img/'.$name);
            $url_pic = $name;
        }
        else {
            if($_POST['url_pic']){
                $url_pic=$_POST['url_pic'];
            }
        }
        $sql = "UPDATE news SET news.title=?, news.description=?, news.text=?, news.date=?, news.url_pic=?, news.heading=? WHERE news.id=?";
        $sth = $dbh->prepare($sql) ;
        $sth->execute(array($_POST['title'], $_POST['description'], $_POST['text'], $_POST['date'], $url_pic, $_POST['heading'], $_POST['id']));
        print_r(json_encode($data));
    }
    if($_POST['type']=='getNews'){
        $sql = "SELECT news.*, count(likes.id) as likes FROM news left JOIN likes on likes.news_id=news.id GROUP BY news.id ORDER BY news.date DESC ";
        $sth = $dbh->prepare($sql) ;
        $sth->execute();
        $data['news']=$sth->fetchAll(PDO::FETCH_ASSOC);
        $sql = "SELECT * FROM heading";
        $sth = $dbh->prepare($sql) ;
        $sth->execute();
        $data['headers']=$sth->fetchAll(PDO::FETCH_ASSOC);
        print_r(json_encode($data));
    }
    if($_POST['type']=='getNewsById'){
        $sql = "SELECT news.*, count(c1.id) as likes, count(DISTINCT c2.id) as liked FROM news left JOIN likes c1 on c1.news_id=news.id left JOIN likes c2 on (c2.session=? and c2.news_id=news.id)  WHERE news.id= ? ";
        $sth = $dbh->prepare($sql) ;
        if ($sth->execute(array($_SESSION['id'], $_POST['id']))) {
          $data['news'] = $sth->fetchAll(PDO::FETCH_ASSOC);
          $sql = "SELECT * FROM heading";
          $sth = $dbh->prepare($sql) ;
          $sth->execute();
          $data['headers']=$sth->fetchAll(PDO::FETCH_ASSOC);
          print_r(json_encode($data));
        }
        else {
                print_r($dbh->errorInfo());
            }
    }
    if($_POST['type']=='delNewsById') {
        $sql = "DELETE news, likes FROM news left join likes ON likes.news_id=news.id where news.id= ?";
        $sth = $dbh->prepare($sql);
        if($sth->execute(array($_POST['id']))) {
          print_r('delete');
        }
        else {
          print_r($dbh->errorInfo());
        }
    }
    if($_POST['type']=='liked'){
        $sql = "SELECT * FROM likes WHERE news_id= ? and session= ?";
        $sth = $dbh->prepare($sql) ;
        $sth->execute(array($_POST['id'], $_SESSION['id']));
        if($sth->fetch(PDO::FETCH_ASSOC)){
            $sql = "DELETE FROM likes WHERE news_id= ? and session= ?";
            $sth = $dbh->prepare($sql) ;
            $sth->execute(array( $_POST['id'], $_SESSION['id']));
        }
        else{
            $sql = "INSERT INTO `likes` (`news_id`, `session`) VALUES (:news_id, :session)";
            $sth = $dbh->prepare($sql) ;
            $sth->execute(array( 'news_id'=>$_POST['id'], 'session'=>$_SESSION['id']));
        }
        $sql = "SELECT news.*, count(c1.id) as likes, count(DISTINCT c2.id) as liked FROM news left JOIN likes c1 on c1.news_id=news.id left JOIN likes c2 on (c2.session=? and c2.news_id=news.id)  WHERE news.id= ?";
        $sth = $dbh->prepare($sql) ;
        if ($sth->execute(array($_SESSION['id'], $_POST['id']))) {
            $data['news'] = $sth->fetchAll(PDO::FETCH_ASSOC);
            print_r(json_encode($data ));
        }
        else {
            print_r($dbh->errorInfo());
        }
    }
    $sth = null;
    $dbh = null;
}
?>