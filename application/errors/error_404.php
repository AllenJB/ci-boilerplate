<!DOCTYPE html>
<html lang="en">
<head>
<title>404 Page Not Found</title>
<style type="text/css">

::selection{ background-color: #E13300; color: white; }
::moz-selection{ background-color: #E13300; color: white; }
::webkit-selection{ background-color: #E13300; color: white; }

body {
	background-color: #fff;
	margin: 40px;
	font: 13px/20px normal Helvetica, Arial, sans-serif;
	color: #4F5155;
}

a {
	color: #003399;
	background-color: transparent;
	font-weight: normal;
}

h1 {
	color: #444;
	background-color: transparent;
	border-bottom: 1px solid #D0D0D0;
	font-size: 19px;
	font-weight: normal;
	margin: 0 0 14px 0;
	padding: 14px 15px 10px 15px;
}

code {
	font-family: Consolas, Monaco, Courier New, Courier, monospace;
	font-size: 12px;
	background-color: #f9f9f9;
	border: 1px solid #D0D0D0;
	color: #002166;
	display: block;
	margin: 14px 0 14px 0;
	padding: 12px 10px 12px 10px;
}

#container {
	margin: 10px;
	border: 1px solid #D0D0D0;
	-webkit-box-shadow: 0 0 8px #D0D0D0;
}

p {
	margin: 12px 15px 12px 15px;
}
</style>
</head>
<body>
    <div id="container">
        <h1>404 Page Not Found</h1>
        <p>
            <?php
            $messages = array (
                'Rather than a beep<br /> Or a rude error message,<br /> These words: “File not found.”',
                'The Web page you seek<br />cannot be located but <br /> endless others exist',
                'You step in the stream,<br />but the water has moved on.<br />This page is not here.',
                'This page has been moved.<br />We’d tell you where, but then we’d<br />have to delete you.',
                'With searching comes loss <br />And the presence of absence: <br />This page was not found.',
                'Something you entered <br />transcended parameters.<br /> So much is unknown.',
                '"404 not found"<br />You deserve a kinder note<br />Like this web haiku',
                "Just a dumb machine.<br />Accuse me, I'm confusing -- <br />Which page did you want?",
                'You have lost your way<br />Have you tried Hare Krishna<br />Or a new address?',
                'Your file is not found<br />If you must file a complaint<br />Send it to Bill Gates',
                'It is not money <br />It is missing web pages<br />At root of evil',
                'You boldly go where <br />No web page has gone before<br />Beam me up, Scotty',
                'File can not be found <br />Maybe you are better off <br />Take a break, play golf!',
                'You have the net but<br />the elusive butterfly<br />cannot be caught here',
                'Where are the snows of <br />yesteryear? Vanished, like this<br />page you hoped to find.',
                'Was it your typing? <br />Or has this web page been moved? <br />Either way: Not here.',
                'Looking into space <br />You find the stars have shifted.<br />Where has the page gone?',
                'No trail of breadcrumbs. <br />You are lost in the forest.<br />Is help on the way?',
                'You come here often?<br />To travel web can confuse<br />Moved this page on you',
                'An err has been made,<br />if you or us hard to say,<br />this page: gone! away!',
                'I ate your Web page. <br />Forgive me. It was juicy <br />And tart on my tongue.',
            );

            $key = array_rand($messages);
            print $messages[$key];
            ?>
        </p>
    </div>
</body>
</html>