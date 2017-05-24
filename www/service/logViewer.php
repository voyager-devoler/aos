<?php
require_once '../../config.php';
if (isset($_GET['com']) && $_GET['com']=='clear')
{
    dbLink::getDB()->query('truncate table dev_logger');
    header('Location: /service/logViewer.php');
}
?>
<html>
    <head>
        <script language="javaScript" src="/js/jquery-1.6.4.min.js"></script>
        <style>
            #container {font-family:Courier;font-size:11px;}
            #container div {float: left}
            #container a.show {display: block; text-decoration: none; color:black; white-space: nowrap;}
            #container a:hover {background-color: #def; cursor: pointer}
            #container a span {color: graytext}
            .btn {padding:5px; border:1px solid gray; border-radius: 3px; background-color: #ccc; color:gray; text-decoration: none;}
            .btn:hover {border-color: red; background-color: #faa; color:red;}
            #top {text-align: center; height: 30px;}
        </style>
        <script language="javaScript">
            $(document).ready(function(){
                $("#container a.show").click(function(){
                   $.post('/service/getLine.php?line='+this.id);
                })
            });
        </script>
    </head>
    <body>
        <div id='top'>
        <a href="logViewer.php?com=clear" class="btn">Clear log</a>
        </div>
<?php
dbLink::getDB();
if (isset($_GET['ip']))
    $ip = $_GET['ip'];
else
    $ip = DBSIMPLE_SKIP;
if (isset($_GET['c']))
    $count = $_GET['c'];
else
    $count = 200;
$commands = dbLink::getDB()->select('SELECT id,time,INET_NTOA(ip) as ip,post,response FROM dev_logger WHERE 1 {AND INET_NTOA(ip)=?} order by id desc limit ?d',$ip, $count);
echo '<div id="container">';
foreach ($commands as $command)
{
    $color = '';
    $res = json_decode($command['response'],true);
    if (isset($res[0]['status']) && $res[0]['status'] == 'error')
        $color = 'style = "color:#f80"';
    if (json_last_error()!==JSON_ERROR_NONE)
        $color = 'style = "color:red"';
    echo '<div>'.$command['id'].' <a href="?ip='.$command['ip'].'">ip</a> | </div>'.'<a class="show" '.$color.' id="'.$command['id'].'" title="'.htmlspecialchars(substr($command['response'],0,100)).'">'.$command['time'].' | '.$command['ip'].' | <span '.$color.'>'.$command['post'].'</span></a>';
}
echo '</div>';
?>
    </body>
</html>


