<?php

if(session_status() === PHP_SESSION_NONE){

    session_start();

}

function isLoggedIn()
{
    return isset($_SESSION['user']);
}

function redirect($url)
{
    header("Location: $url");
    exit();
}

function requireLogin()
{
    if(!isLoggedIn()){

        redirect("login.php");

    }
}

function logout()
{
    session_destroy();

    redirect("home.php");
}

/* =========================
   THEME SYSTEM
========================= */

function getThemeBackground()
{

    global $conn;

    if(!isset($_SESSION['user_id'])){

        return "#050816";

    }

    $userId =
    $_SESSION['user_id'];

    $query =
    mysqli_query(
        $conn,
        "
        SELECT custom_bg
        FROM users
        WHERE id='$userId'
        "
    );

    if(!$query){

        return "#050816";

    }

    $user =
    mysqli_fetch_assoc($query);

    return
    $user['custom_bg']
    ??
    "#050816";

}

function getGridColor()
{

    global $conn;

    if(!isset($_SESSION['user_id'])){

        return "#ffffff15";

    }

    $userId =
    $_SESSION['user_id'];

    $query =
    mysqli_query(
        $conn,
        "
        SELECT custom_grid
        FROM users
        WHERE id='$userId'
        "
    );

    if(!$query){

        return "#ffffff15";

    }

    $user =
    mysqli_fetch_assoc($query);

    return
    $user['custom_grid']
    ??
    "#ffffff15";

}

function getThemeStyles()
{

    $bg =
    getThemeBackground();

    $grid =
    getGridColor();

    return "

    --bg-color:$bg;
    --grid-color:$grid;

    ";

}

?>